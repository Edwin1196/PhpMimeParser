# PhpMimeParser
Php mime message parser

### How to

```php
// Load .eml mime message from file
$str = file_get_contents('mime-mixed-related-alternative.eml');

// Format output
echo "<pre>";

// Create object MimeParser
$m = new PhpMimeParser($str);

// Show Emails
print_r($m->mTo);
print_r($m->mFrom);
print_r($m->mBcc);
print_r($m->mCc);

// Show Message
echo $m->mSubject;
echo $m->mHtml;
echo $m->mText;
print_r($m->mInlineList);

// Show Files
print_r($m->mFiles);

// Save attachments to folder
foreach ($m->mFiles as $key => $file) {	
	$dir = 'attachments';
	if (!file_exists($dir)) {
	  mkdir($dir);
	}	
	//  Save content to file
	file_put_contents($dir.'/'.basename($file['name']), $file['content']);
}

// Get header line with Date:
echo $m->getFromHeader('Content-Type');
echo $m->getFromHeader('Bcc');

```

# PhpMimeClient
Create mime message in php

# How to

```php

// Create object
$m = new PhpMimeClient();

// Add to
$m->addTo("email@star.ccc", "Albercik");
$m->addTo("adela@music.com", "Adela");

// Add Cc
$m->addCc("hello@email.be", "Ben");
$m->addCc("zonk@email.au");

// Add Bcc
$m->addBcc("boos@domain.com", "BOSS");    

// Add files inline
$m->addFile('photo.jpg',"zenek123");

// Add file
$m->addFile('sun.png');

// create mime
$m->createMime("Witaj księżniczko Alabambo",'<h1>Witaj księżniczko Alabambo <img src="cid:zenek123"> </h1>',"Wesołych świąt życzę!","Heniek Wielki", "heniek@domain.com");

// get mime
// $m->getMime();

// Show mime
echo nl2br(htmlentities($m->getMime()));

```

