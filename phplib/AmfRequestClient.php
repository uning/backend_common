<?php
require_once '../../public/base.php';
require_once 'Zend/Amf/Request.php';




class AmfRequestClient extends Zend_Amf_Request{

	/**
	 *  @var Zend_Amf_Parse_OutputStream
	 * 
	 */

	protected $_objectEncoding;
	public    $post_data;  // post to server via HTTP POST
	public    $resp_data;   // the server response
	public    $amf_gateway='http://tingkun.playcrab.com/amf_gateway.php';

	/**
	 * init a request
	 *
	 * @param string $target_uri
	 * @param stdClass or Object $data
	 * @param string  $response_Uri
	 *
	 **/
	public function AmfRequestClient($target_uri='',$data=null,$response_Uri='/1')
	{
		$this->_bodies[]=new Zend_Amf_Value_MessageBody($target_uri,$response_uri,array($data));
		$this->_bodies[0]->setResponseUri($response_Uri);
		$this->setObjectEncoding(Zend_Amf_Constants::AMF3_OBJECT_ENCODING);
	}

	public function getHttpPost()
	{
		//if($this->post_data)
		//	return $this->post_data;

		$stream = new Zend_Amf_Parse_OutputStream();

		$stream->writeByte(0x00);
		$stream->writeByte($this->_objectEncoding);

		$headerCount = count($this->_headers);
		$stream->writeInt($headerCount);
		foreach ($this->getAmfHeaders() as $header) {
			$serializer = new Zend_Amf_Parse_Amf0_Serializer($stream);
			$stream->writeUTF($header->name);
			$stream->writeByte($header->mustRead);
			$stream->writeLong(Zend_Amf_Constants::UNKNOWN_CONTENT_LENGTH);
			$serializer->writeTypeMarker($header->data);
		}

		// loop through the AMF bodies that need to be returned.
		$bodyCount = count($this->_bodies);
		$stream->writeInt($bodyCount);
		foreach ($this->_bodies as $body) {
			$serializer = new Zend_Amf_Parse_Amf0_Serializer($stream);
			$stream->writeUTF($body->getTargetURI());
			$stream->writeUTF($body->getResponseURI());
			$stream->writeLong(Zend_Amf_Constants::UNKNOWN_CONTENT_LENGTH);
			if($this->_objectEncoding == Zend_Amf_Constants::AMF0_OBJECT_ENCODING) {
				$serializer->writeTypeMarker($body->getData());
			} else {
				// Content is AMF3
				$serializer->writeTypeMarker($body->getData(),Zend_Amf_Constants::AMF0_AMF3);
			}
		}
		return $this->post_data=$stream->getStream();
	}


	/**
	 */
	
	public function doRequest()
	{
		$host_info=parse_url($this->amf_gateway);
		$header = array(
			'Accept: */*'
			,'Accept-Encoding:	gzip, deflate'
			,'Accept-Language:	zh-CN'
			,'Cache-Control: no-cache'
			,'Content-Type: application/x-amf'
			,"Host: {$host_info['host']}"
			,'Referer:	http://localhost/hotel/app.swf?v=2'
			,'UA-CPU:	x86'
			,'User-Agent:	Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; InfoPath.2)'
			,'x-flash-version:	10,0,22,87'
			);

			$ch    = curl_init();
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);

			//curl_setopt($ch, CURLOPT_VERBOSE, true);


			curl_setopt($ch,CURLOPT_URL,$this->amf_gateway);

			curl_setopt($ch, CURLOPT_POSTFIELDS,$this->getHttpPost());
			$this->resp_data =  curl_exec($ch);
			curl_close($ch);
	}




}
return ;


//for test
/*
 $data['tnd']   = 2;
 $data['session_key'] = 'skvalue';
 $data['pmid']  = 1111;
 //*/
//*
$data =new stdClass;
$data->tnd=2;
$data->session_key = 'skvalue';
$data->pmid = 1111;

//*/
$myreq=new AmfRequestClient('TestAmf.sendback',$data);
$myreq->doRequest();
echo "Request:\n{$myreq->post_data}\n\nResponse:\n{$myreq->resp_data}\n";
return;
$req=new Zend_Amf_Request;
$req->initialize($sreq);
var_dump($myreq);
var_dump($req);

//$myreq->doRequest();

