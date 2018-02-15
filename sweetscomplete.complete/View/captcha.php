<?php
set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());

// load CAPTCHA library files
require_once 'PEAR.php';
require_once 'Text/CAPTCHA.php';

class MakeCaptcha
{
	
	// initialize variables
	public $captchaDir 		= '';
	public $expTime 		= '';
	public $captchaURL		= '';
	public $captchaFile 	= '';
	public $captchaError	= '';
	public $captchaPhrase	= '';
	
	public function __construct()
	{
		$this->captchaDir 	= __DIR__ . '/../captcha/';
		$this->captchaFile 	= md5(session_id()) . '.png';
		$this->captchaURL	= HOME_URL . '/captcha/' . $this->captchaFile;
		$this->expTime 		= time() - 360;	// 1 minute ago
	}
	
	public function purgeCaptchaFiles()
	{
		// remove old captcha files
		foreach (new DirectoryIterator($this->captchaDir) as $fileInfo) {
			if($fileInfo->isFile()) {
				if ($fileInfo->getMTime() < $this->expTime) {
					unlink($this->captchaDir . DIRECTORY_SEPARATOR . $fileInfo->getFilename());
				}
			}
		}
	}

	public function generateCaptcha()
	{
		$success = TRUE;
		// Set CAPTCHA options (font must exist!)
		$imageOptions = array(
				'font_size'        => 24,
				'font_path'        => __DIR__,
				'font_file'        => 'FreeSansBold.ttf',
				'text_color'       => '#593125',
				'lines_color'      => '#2CA242',
				'background_color' => '#FF6201'
		);
		
		// Set CAPTCHA options
		$options = array(
				'width' => 200,
				'height' => 80,
				'output' => 'png',
				'imageOptions' => $imageOptions
		);
		
		// Generate a new Text_CAPTCHA object, Image driver
		$c = Text_CAPTCHA::factory('Image');
		$retval = $c->init($options);
		if (PEAR::isError($retval)) {
			$this->captchaError = 'Error initializing CAPTCHA';
			$success = FALSE;
		}
		
		// Get CAPTCHA image (as PNG)
		$png = $c->getCaptcha();
		if (PEAR::isError($png)) {
			$this->captchaError = 'Error generating CAPTCHA!';
			$success = FALSE;
		} else {
			file_put_contents($this->captchaDir . $this->captchaFile, $png);
		}
		
		// store secret passphrase
		$this->captchaPhrase = $c->getPhrase();
		
		// return CAPTCHA secret passphrase
		return $success;
	}

}
