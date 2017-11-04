<?php
// Create mime message in php
/**
* PhpMimeClient create multipart mime message https://pl.wikipedia.org/wiki/Multipurpose_Internet_Mail_Extensions
* @autor
* Marcin Łukaszewski hello@breakermind.com
*/
class PhpMimeClient
{
    public $mime;
    public $filesList;
    // charset: utf-8, utf-16, iso-8859-2, iso-8859-1
    public $mEncoding = 'UTF-8';

    function __construct($Encoding = 'UTF-8'){
        $this->mEncoding = $Encoding;
    }

    function addFile($filePath, $ContentID = ""){
        $i = count($this->filesList)+1;
        $this->filesList[$i]['path'] = $filePath;
        $this->filesList[$i]['cid'] = $ContentID;
    }

    function createMime($msgText, $msgHtml, $subject, $fromName, $fromEmail, $to, $replyTo = "", $cc = "" , $bcc = ""){     
        
        if (empty($replyTo)) { $replyTo = $fromEmail; }
        // simple message
        // $header .= "Content-type: text/html; charset=".$this->mEncoding." \r\n";
        // random strings
        $tm = time();
        $boundary1 = md5($tm);
        $boundary2 = md5($tm-10);

        // multipart message
        $header = "Date: ".date("r (T)")." \r\n";        
        $header .= "From: ".$fromName." <".$fromEmail."> \r\n";        
        $header .= "Subject: =?".$this->mEncoding."?B?".base64_encode($subject)."?=\r\n";
        $header .= "Reply-To: <".$replyTo.">\r\n";
        $header .= "Return-Path: <".$fromEmail.">\r\n";         
        $header .= "MIME-Version: 1.0 \r\n";
        $header .= "Content-Transfer-Encoding: 8bit \r\n";        
        $header .= "Content-Type: multipart/mixed; boundary=\"$boundary1\"\r\n\r\n";   
        $header .= "--$boundary1\r\n";
        $header .= "Content-Type: multipart/alternative; boundary=\"$boundary2\"\r\n\r\n";
        $header .= "--$boundary2\r\n";
        $header .= "Content-Type: text/plain; charset=\"".$this->mEncoding."\"\r\n";
        $header .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $header .= quoted_printable_encode($msgText)."\r\n\r\n";
        $header .= "--$boundary2\r\n";
        $header .= "Content-Type: text/html; charset=\"".$this->mEncoding."\"\r\n";
        $header .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $header .= quoted_printable_encode($msgHtml)."\r\n\r\n";
        $header .= "--$boundary2--\r\n";
        // add atachments
        if(count($this->filesList) > 0){
            foreach ($this->filesList as $f) {
                // file name and id if inline image
                $path = $f['path'];
                $cid = $f['cid'];
                if(file_exists($f['path'])){
                    // create mime file
                    $file = basename($path);
                    $filecontent = base64_encode(file_get_contents($path));
                    $extension = pathinfo(basename($path), PATHINFO_EXTENSION);
                    $mimetype = mime_content_type($path);
                    // cout << "MIME " << mimetype << endl << extension << endl;
                    // cout << "FILE CONTENT " << fc << endl;
                    $header .= "--$boundary1\r\n";
                    $header .= "Content-Type: ".$mimetype."; name=\"".$file."\"\r\n";
                    $header .= "Content-Transfer-Encoding: base64\r\n";                
                    if(!empty($cid)){
                        // if inline image
                        $header .= "Content-Disposition: attachment; filename=\"".$file."\"\r\n";
                        $header .= "Content-ID: <".$cid.">\r\n\r\n";
                    }else{
                        $header .= "Content-Disposition: attachment; filename=\"".$file."\"\r\n\r\n";
                    }
                    $header .= $filecontent."\r\n\r\n";
                }
            }
        }
        $header .= "--$boundary1--\r\n\r\n";
        $header .= "\r\n.\r\n";
        // add mime
        $this->mime = $header;
    }

    function getMime(){
        return $this->mime;
    }

}

// Create mime object
$m = new PhpMimeClient();

// Add files inline
$m->addFile('photo.jpg',"zenek123");

// Add file
$m->addFile('sun.png');

// create mime
$m->createMime("Witaj księżniczko Alabambo",'<h1>Witaj księżniczko Alabambo <img src="cid:zenek123"> </h1>',"Wesołych świąt życzę!","Heniek Wielki", "heniek@breakermind.com","hello@gomail.coc");

// Show mime
echo nl2br(htmlentities($m->getMime()));

?>
