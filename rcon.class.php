<?php
/*
	Basic CS 1.6 Rcon
    ----------------------------------------------
    Contact: printf("%s%s%s%s%s%s%s%s%s%d%s%s%s","rc","on",chr(46),"cl","ass",chr(64),"pri","ya",chr(46),2,"y",chr(46),"net")

*/

define("SERVERDATA_EXECCOMMAND",2);
define("SERVERDATA_AUTH",3);

class RCon {
    public $Password;
    public $Host;
    public $Port = 27015;
    public $_Sock;
    public $_Id = 0;
	public $Socket;

    function RCon ($Host,$Port,$Password) {
	$this->Password = $Password;
	$this->Host = $Host;
	$this->Port = $Port;
	$this->Socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	$this->_Sock = socket_connect($this->Socket, $this->Host,$this->Port) or
	    die("Unable to open socket: \n");
	# $this->_Set_Timeout($this->_Sock,2,500);
    }
    
    function Auth () {
	$PackID = $this->_Write(SERVERDATA_AUTH,$this->Password);
	
	// Real response (id: -1 = failure)
	$ret = $this->_PacketRead();
	if ($ret == -1) {
	    die("Authentication Failure\n");
	}
    }

    function _Set_Timeout(&$res,$s,$m=0) {
	if (version_compare(phpversion(),'4.3.0','<')) {
	    return socket_set_timeout($res,$s,$m);
	}
	return stream_set_timeout($this->_Sock,$s,$m);
    }

    function _Write($cmd, $s1='', $s2='') {
	// Get and increment the packet id
	$id = ++$this->_Id;

	// Put our packet together
	$data = pack("VV",$id,$cmd).$s1.chr(0).$s2.chr(0);

	// Prefix the packet size
	$data = pack("V",strlen($data)).$data;
	$handle = fopen("C:/source/test.txt", "r+b");
	// Send packet
	fwrite($handle,$data,strlen($data));

	// In case we want it later we'll return the packet id
	return $id;
    }

    function _PacketRead() {
	//Declare the return array
	$retarray = array();
	//Fetch the packet size
	$handle = fopen("C:/source/test.txt", "r+b");
	
	while ($size = @fread($handle,4)) {
	    $size = unpack('V1Size',$size);
	    //Work around valve breaking the protocol
	    if ($size["Size"] > 4096) {
		//pad with 8 nulls
		$packet = "\x00\x00\x00\x00\x00\x00\x00\x00".fread($handle,4096);
	    } else {
		//Read the packet back
		$packet = fread($handle,$size["Size"]);
	    }
	    array_push($retarray,unpack("V1ID/V1Response/a*S1/a*S2",$packet));
	}
	return $retarray;
    }

    function Read() {
	$Packets = $this->_PacketRead();
	
	foreach($Packets as $pack) {
	    if (isset($ret[$pack['ID']])) {
		$ret[$pack['ID']]['S1'] .= $pack['S1'];
		$ret[$pack['ID']]['S2'] .= $pack['S1'];
	    } else {
		$ret[$pack['ID']] = array(
					'Response' => $pack['Response'],
					'S1' => $pack['S1'],
					'S2' =>	$pack['S2'],
				    );
	    }
	}
	return $ret;
    }

    function sendCommand($Command) {
	$Command = '"'.trim(str_replace(' ','" "', $Command)).'"';
	$this->_Write(SERVERDATA_EXECCOMMAND,$Command,'');
    }

    function rconCommand($Command) {
	$this->sendcommand($Command);

	$ret = $this->Read();

	return $ret[$this->_Id]['S1'];
    }
}
?>
