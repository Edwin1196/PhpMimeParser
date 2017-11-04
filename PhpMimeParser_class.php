<?php

/**
* Parse mime message message part https://pl.wikipedia.org/wiki/Multipurpose_Internet_Mail_Extensions
* @autor
* Marcin Łukaszewski hello@breakermind.com
*/
class PhpMimeParser{
	// Subject mime
	// '=?utf-8?B?'.base64_encode($subject).'?='
	// =?charset?encoding?encoded-text?=
	// =?utf-8?Q?hello?=
	// =?UTF-8?B?4pyIIEJvc3RvbiBhaXJmYXJlIGRlYWxzIC0gd2hpbGUgdGhleSBsYXN0IQ==?=
	public $mime, $MultiParts, $allMessage;
	// Content
	public $mHeader;
	public $mSubject;
	public $mHtml;
	public $mText;
	public $mFiles;
	public $mTo;
	public $mFrom;
	public $mCc;
	public $mBcc;
	public $mInlineList;

	function __construct($mimeMessage){	
		error_reporting(E_ERROR | E_PARSE | E_STRICT);
		// remove mime message end dot
		$mimeMessage = str_replace("\r\n.\r\n","",$mimeMessage);
		$this->allMessage = $mimeMessage;
		$this->mTo = $this->getEmails('To');
		$this->mFrom = $this->getEmails('From');
		$this->mCc = $this->getEmails('Cc');
		$this->mBcc = $this->getEmails('Bcc');
		// $this->cutEMails($mimeMessage);
		$this->cutSubject($mimeMessage);
		// sut all parts alternative related mixed		
		$this->getParts($mimeMessage);
		// get simple body
		$this->getSimpleBody();
		$p = 1;
		foreach ($this->MultiParts as $part) {
			if(!mb_check_encoding($part[1], 'UTF-8')){
				$part = mb_convert_encoding($part[1], "UTF-8", "auto");
				// iconv(mb_detect_encoding($part, mb_detect_order(), true), "UTF-8", $part);
			}
			$this->setMimePart($part);
			switch ($this->getContentType()) {
				case 'text/html':
					# Html content
					if(strpos($part[0],'quoted-printable') > 0){
						$this->mHtml = html_entity_decode(quoted_printable_decode($part[1]));
					}else if(strpos($part[0],'base64') > 0){
						$this->mHtml = html_entity_decode(base64_decode($part[1]));
					}else{
						$this->mHtml = html_entity_decode($part[1]);
					}					
					break;
				case 'text/plain':
					# Text content
					if(strpos($part[0],'quoted-printable') > 0){
						$this->mText = quoted_printable_decode($part[1]);
					}else if(strpos($part[0],'base64') > 0){
						$this->mText = base64_decode($part[1]);
					}else{
						$this->mText = $part[1];
					}
					break;
				default:
					# File
					$file = NULL;
					$file['name'] = $this->getFileName();
					if ($this->getFileEncoding() == 'base64') {
						$file['content'] = $this->isFileBase64();
					}else if($this->getFileEncoding() == 'quoted-printable'){
						$file['content'] = $this->isFileQuoted();
					}else{
						$file['content'] = $this->isFile();
					}					
					$file['type'] = $this->getFileEncoding();
					$file['inline'] = $this->getInlineID();
					// set list with inline images
					$this->mInlineList['cid:'.$this->getInlineID()] = $this->getFileName();
					// add file
					$this->mFiles[$p] = $file;
					break;
			}
			$p++;
		}
		error_reporting(E_ALL);
	}

	function cutEMails($str){
		preg_match_all('/(?<=((\n)To:)|(^To:))(.*)+?(?=())/', $str, $to);
		// preg_match_all('/To:(.*)/', $str, $to);
		echo "To " . htmlentities($to[0][0]);

		preg_match_all('/(?<=((\n)From:)|(^From:))(.*)+?(?=())/', $str, $from);
		// preg_match_all('/From:(.*)/', $str, $to);
		echo "From " . htmlentities($to[0][0]);

		preg_match_all('/(?<=((\n)Subject:)|(^Subject:))(.*)+?(?=())/', $str, $subject);		
		echo "Subject " . $subject[0][0];
	}

	function getEmails($str){
		$emails = array();		
		$name = "";
		preg_match_all('/(?<=((\n)'.$str.':)|(^'.$str.':))(.*)+?(?=())/', $this->allMessage, $out);
		// preg_match_all('/To:(.*)/', $this->allMessage, $out);
		// print_r($out);
		if(count($out[0]) != NULL){
			$out = str_ireplace($str.':', '', $out[0][0]);
			$out = str_ireplace('<', '', $out);
			$out = str_ireplace('>', '', $out);
			$out = explode(',', $out);
			$jj = 0;
			foreach ($out as $v) {			
				$x = explode(" ",$v);
				// email
				$emails[$jj]['email'] = end($x);			
				// name
				for ($i = 0; $i < (count($x)-1); $i++) {
					$name .=  $x[$i] . ' ';
				}			
				$emails[$jj]['name'] = $name;
				$jj++;
				$name = "";
			}
		}
		return $emails;
	}

	function cutSubject($str){
		preg_match_all('/(?<=((\n)Subject:)|(^Subject:))(.*)+?(?=())/', $str, $subject);
		// echo "Subject " . $subject[0][0];
		$s = $subject[0][0];
		if(!mb_check_encoding($s, 'UTF-8')){
			$s = mb_convert_encoding($s, "UTF-8", "auto");
			// iconv(mb_detect_encoding($part, mb_detect_order(), true), "UTF-8", $part);
		}
		if (strpos($s, '?Q?') > 0 || strpos($s, '?q?') > 0) {
			$encoding = explode('?', $s)[1];
			$s = str_ireplace("=?".$encoding."?Q?", "", $s);
		    $s = str_replace("?=", "", $s);
		    $s = quoted_printable_decode($s);
		}else if (strpos($s, '?B?') > 0 || strpos($s, '?b?') > 0){
			$encoding = explode('?', $s)[1];			
			$s = str_ireplace("=?".$encoding."?B?", "", $s);
		    $s = str_replace("?=", "", $s);
		    $s = base64_decode($s);
		}		
		$this->mSubject = $s;
	}

	function countParts(){
		return count($this->MultiParts);
	}

	function isFile(){
		if (strpos($this->mime[0],'Content-Disposition:') > 0) {				
		  	return trim($this->mime[1]);			
		}
		return 0;
	}

	function isFileQuoted(){
		if (strpos($this->mime[0],'Content-Disposition:') > 0) {				
	  		return trim(quoted_printable_decode($this->mime[1]));
		}		
		return 0;
	}

	function isFileBase64(){
		if (strpos($this->mime[0],'Content-Disposition:') > 0) {				
			return trim(base64_decode($this->mime[1]));
		}		
		return 0;
	}

	function setMimePart($mimePart){
		$this->mime = $mimePart;
	}

	function getFileName(){
		preg_match_all('/Content-Disposition:(.*)/', $this->mime[0], $out);
		if (empty($out[0][0])) {
			return 0;
		}
		$str = html_entity_decode($out[0][0]);		
		preg_match_all('/(?<=(filename="))(.*)?(?=("))/', $str, $file);
		if (!empty($file[0][0])) {
			return trim($file[0][0]);
		}
		return 0;
		// echo mb_check_encoding($out[0][0], 'UTF-8');		// chr(34)		 		
		// $str = str_replace('Content-Disposition: attachment; filename=', "", $str);		
		// return $str = str_replace('"', "", $str);
	}

	function getFileEncoding(){
		preg_match_all('/Content-Transfer-Encoding:(.*)/', $this->mime[0], $out);
		if (empty($out[0][0])) {
			return 0;
		}
		$str = html_entity_decode($out[0][0]);		
		$f = explode(":", $str);
		if (!empty($f[1])) {
			return trim($f[1]);
		}
		return 0;
	}

	function getInlineID(){
		preg_match_all('/Content-ID:(.*)/', $this->mime[0], $out);
		if (empty($out[0][0])) {
			return 0;
		}
		$str = html_entity_decode($out[0][0]);
		if ($str != NULL) {
			preg_match_all('/(?<=(<))(.*)?(?=(>))/', $str, $file);
			if (!empty($file[0][0])) {
			return trim($file[0][0]);
			}
		}
		return 0;		
	}

	function getContentType(){
		preg_match_all('/Content-Type:(.*)/', $this->mime[0], $out);
		if (empty($out[0][0])) {
			return 0;
		}
		$str = html_entity_decode($out[0][0]);
		if ($str != NULL) {
			preg_match_all('/(?<=(:))(.*)?(?=(;))/', $str, $file);
			if (!empty($file[0][0])) {
			return trim($file[0][0]);
			}
		}
		return 0;		
	}

	function getHtmlMsg(){
		foreach ($this->MultiParts as $key => $value) {
			if (strpos($value[0],'text/html') > 0 ) {			
				if(strpos($value[0],'quoted-printable') > 0){
					return quoted_printable_decode($value[1]);
				}else if(strpos($value[0],'base64') > 0){
					return base64_decode($value[1]);
				}else{
					$value[1];
				}
			}			
		}
		return "";		
	}

	function getTextMsg(){
		foreach ($this->MultiParts as $key => $value) {
			if (strpos($value[0],'text/plain') > 0 ) {			
				if(strpos($value[0],'quoted-printable') > 0){
					return quoted_printable_decode($value[1]);
				}else if(strpos($value[0],'base64') > 0){
					return base64_decode($value[1]);
				}else{
					$value[1];
				}
			}			
		}
		return "";		
	}

	function getSimpleBody(){
		if (count($this->MultiParts) == 0) {
			$this->mHtml = explode("\r\n\r\n", $this->allMessage)[1];
			$this->mText = $this->mHtml;
		}
	}

	function getParts($message){
		preg_match_all('/((?<=(Content-Type: multipart\/mixed; boundary="))(.*)?(?=(")))|((?<=(Content-Type: multipart\/related; boundary="))(.*)?(?=(")))|((?<=(Content-Type: multipart\/alternative; boundary="))(.*)?(?=(")))/', $message, $boundary);

		// echo "<pre>";
		// print_r($boundary);

		$AllPartsUnique = "";
		$j=0;

		foreach ($boundary[0] as $key => $v) {
		  if($key >= 0){
		    // echo "\n\n\nBoundary " . $v . "\r\n";

		    // cut boundary content
		    preg_match_all('/(?<=(--'.$v.'))(| |.*|[\s\S]+|\<|\>|\.|\r|\n|\0|@|\w+)?(?=(--'.$v.'--))/', $message, $part);
		    // print_r($part);
		    $bname = $v;

		    foreach ($part[0] as $v) {
		        // echo "PART " . $bname . " " . $v . "\r\n";
		        $parts = explode("--".$bname, $v);

		        // echo "<pre>";
		        foreach ($parts as $v) {
		            // echo "\r\nSINGLE PART " . $v . "\r\n";     
		            // $AllPartsUnique[$j] = $v;
		            // with html visible on page
		            $AllPartsUnique[$j] = htmlentities($v);     
		            $j++;  
		        }
		    }
		  }
		}
		foreach($AllPartsUnique as $key => $one) {
		  foreach ($boundary[0] as $find) {
		    if(strpos($one, $find) !== false){
		        unset($AllPartsUnique[$key]); 
		    }
		  }    
		}
		// echo "<pre>";
		// print_r($AllPartsUnique);
		$iii = 0;
		foreach ($AllPartsUnique as $v) {
			$e =  explode("\r\n\r\n", $v);			
			$this->MultiParts[$iii] = $e;			
			$iii++;
		}		
		return $this->MultiParts;
	}
	
	// get line from Header (To for To: , Bcc for Bcc: ...)
	function getFromHeader($str){
		preg_match_all('/'.$str.':(.*)/', $this->allMessage, $out);
		return htmlentities($out[0][0]);
	}	
}

$str = file_get_contents('mime-mixed-related-alternative.eml');

// MimeParser
$m = new PhpMimeParser($str);

// Format output
echo "<pre>";

// Emails
print_r($m->mTo);
print_r($m->mFrom);
print_r($m->mBcc);
print_r($m->mCc);

// Message
echo $m->mSubject;
echo $m->mHtml;
echo $m->mText;
print_r($m->mInlineList);

// Files
print_r($m->mFiles);

// Save file to folder
foreach ($m->mFiles as $key => $file) {	
	$dir = 'attachments';
	if (!file_exists($dir)) {
		mkdir($dir);
	}	
	//  Save content to file
	file_put_contents($dir.'/'.basename($file['name']), $file['content']);
}

// Custom header
echo $m->getFromHeader('Content-Type');



