<?php
/**
 * @package wx.php.core
 */

/**
  *
  * Form processing, validating and parsing class
  * Loads form from /app/view/forms/
 	* @package wx.php.core
  */
 class WXForm extends ApplicationBase
 {
	
 	public $formhtml="";
 	private $formstructure;
 	private $formindex;
 	private $validation_array=array();
 	private $expected_array=array();
 	private $user_values;
 	private $errors_array=array();
 	private $label_mappings;
 	public  $form_values;
 	
 	const LETTERS =				'/^[a-zA-Z\s]*$/';
 	const LETTERS_NUMBERS =		'/^[a-zA-Z0-9\s]*$/';
 	const NUMBERS = 			'/^[0-9\s]*$/';
 	const EMAIL = 				'/^[\w\.=-]*@[\w\.-]*\.[\w\.]{2,5}$/';
 	const DATE = 				'/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}/';
 	const TEXT = 				'/^[\w-_\s\'\"]/';
 	
	 function __construct($formfile, $formid, $strip=true)
	 {
	   	try
	   	  {
				$this->formhtml.='<div>';
				$this->formhtml.=file_get_contents(APP_DIR."view/".$formfile.".html");
				$this->formhtml.='</div>';
				$this->strip_php();
   	  	$xp=xml_parser_create();
				xml_parser_set_option($xp, XML_OPTION_SKIP_WHITE, 1);
   	  	xml_parse_into_struct($xp, $this->formhtml, $this->formstructure, $this->formindex);  	
	   	  }
	   	catch(Exception $e)
	   	  {
	   		$this->process_exception($e);
	   	  }
		$this->isolate_form($formid);
	  $this->parse_form_validation();	
	  $this->set_allowed_values();
	  $this->strip_unexpected(); 
	  $this->map_labels();
	
	  $this->validate_form();
	  if(!$this->is_valid()) { 
			Session::set('errors', $this->errors_array); 
			Session::set('form', $_POST);
		}
	 }
	 
	 public function is_valid()
	 {
	   if(count($this->errors_array)>=1) { return false; }
	   else return true;	
	 }
	 
	 public function custom_validation($field, $pattern)
	 {
	 	if(preg_match($pattern, $this->form_values[$field]))
	 	 { return true; }
	 	else
	 	  { 
	 	  	$this->errors_array[]=$this->label_mappings[$field].' Invalid Format';
	 	  	return false;
	 	  }
	 	 	
	 }
	 
	 public function add_error($message)
	 {
	    $this->errors_array[]=$message;
	    Session::set('errors', $this->errors_array);
	    return true;	
	 }
	
	public function clear_errors() {
		$this->errors_array=array();
		Session::unset_var('erros');
	}
	 
	 public function add_raw($variable, $validation=null)
	 {
	   if($this->user_values[$variable]) {
	     $this->form_values[$variable]=$this->user_values[$variable];  
	   }
	   //$_POST=$this->form_values;
	 }
	
	private function isolate_form($formid) {
		if(is_array($this->formstructure)) {
			$found=0;
	 		foreach($this->formstructure as $array=>$tag) {
				if($tag['tag']=="FORM" && $found==1) {
					$closetag=$array; $found=2;
				}
				if($tag['tag']=="FORM" && $tag['attributes']['ID']==$formid && $found==0) {
					$opentag=$array; $found=1;
				}
			}
			$this->formstructure= array_splice($this->formstructure, $opentag, $closetag-$opentag+1);
		}
	}
	
	private function strip_php() {
		$this->formhtml = preg_replace('/(<\?)[^\?>]*(\?>)/', '', $this->formhtml);
		$this->formhtml = preg_replace('/(\&)\w*(\;)/', '', $this->formhtml);
		$this->formhtml = preg_replace('/(<\?).*(\?>)/', '', $this->formhtml);
	}
	 
	 private function parse_form_validation()
	 {
		if(is_array($this->formstructure)) {
	 		foreach($this->formstructure as $array=>$tag) {
	 	  	if(strlen($tag['attributes']['CLASS'])>0 && strlen($tag['attributes']['NAME'])>0) {
		 	    $class_array=explode(" ", $tag['attributes']['CLASS']);
		 	      foreach($class_array as $class) {
		 	      	$testmatch=preg_match("/^FM[a-zA-Z0-9]*$/", $class);
		 	      	if($testmatch) {
		 	        	$index=$tag['attributes']['NAME'];
								//$index = str_replace('[', '', $index);
								//$index = str_replace(']', '', $index);
		 	        	$this->validation_array[][$index]=$class;
		 	        }
		 	      }
		 	   }	
		 	}
		}	
	 }
	 
	private function set_allowed_values() {
	 	if(is_array($this->formstructure)) {
		 	foreach($this->formstructure as $array=>$tag) {
	     	if($tag['tag']=='INPUT' | $tag['tag']=='TEXTAREA' | $tag['tag']=='SELECT') {
	     	  $this->expected_array[]=$tag['attributes']['NAME'];
					$this->expected_array=array_filter($this->expected_array);
	     	 }
	     }
		}
	}
	 
	private function map_labels() {
		foreach($this->validation_array as $validation) {
			$field=key($validation);
 	    $type=$validation[$field];
			$this->label_mappings[$field]=$this->get_label_value($field);
		}
	 }
	
	private function get_label_value($inputid) {
		if(is_array($this->formstructure)) {
		 	foreach($this->formstructure as $array=>$tag) {
				if($tag['tag']=="LABEL" && $tag['attributes']['FOR']==$inputid) {
					return $tag['value'];
				}
			}
		}
		return false;
	}
	 
	 private function strip_unexpected()
	 {
	 	$this->user_values=array_merge($_GET, $_POST);
	 	foreach($this->expected_array as $key=>$value)
	 	{
	 		if(array_key_exists($value, $this->user_values))
	 		{
	 		   $this->form_values[$value]=$this->user_values[$value];
	 		}
	 	}
	 }
	 
	 private function validate_form()
	 {
		
	   	  foreach($this->validation_array as $validate)
	   	  {
	   	    $field=key($validate);
	   	    $type=$validate[$field];
	   	    $props=explode("-", $type);
	   	      switch($props[0])
	   	        {
	   	          case "FMrequired":
	   	          $valid=$this->valid_exists($this->form_values[$field]);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' is a required field'; }
	   	          break;
	   	          case "FMletters":
	   	          $valid=$this->valid_letters($this->form_values[$field]);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Invalid Format'; }
	   	          break;
	   	          case "FMnumbers":
	   	          $valid=$this->valid_numbers($this->form_values[$field]);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Invalid Format'; }
	   	          break;
	   	          case "FMlettersnumbers":
	   	          $valid=$this->valid_letters_numbers($this->form_values[$field]);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Invalid Format'; }
	   	          break;
	   	          case "FMtext":
	   	          $valid=$this->valid_text($this->form_values[$field]);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Invalid Format'; }
	   	          break;
	   	          case "FMminlength":
	   	          $minlength=$props[1]; $maxlength=100000;
	   	          $valid=$this->valid_length($this->form_values[$field], $minlength, $maxlength);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Field too short'; }
	   	          break;
	   	          case "FMmaxlength":
	   	          $minlength=0; $maxlength=$props[1];
	   	          $valid=$this->valid_length($this->form_values[$field], $minlength, $maxlength);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Field too long'; }
	   	          break;
	   	          case "FMnumberrange":
	   	          $min=$props[1]; $max=$props[2];
	   	          $valid=$this->valid_number_range($this->form_values[$field], $min, $max);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Invalid Number Range'; }
	   	          break;
	   	          case "FMdate":
	   	          $valid=$this->valid_date($this->form_values[$field]);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Invalid Date Format'; }
	   	          break;
	   	          case "FMemail":
	   	          $valid=$this->valid_email($this->form_values[$field]);
	   	          if(!$valid) 
	   	            { $this->errors_array[]=$this->label_mappings[$field].' Invalid Email Format'; }
	   	          break;	
	   	        }
	   	  }
	 }
	 
	 private function valid_exists($string)
	 {
	   if(strlen($string)>0) { return true; }
	   return false;
	 }
	 private function valid_letters($string)
	 {
	   if(preg_match(self::LETTERS, $string)) { return true; }
	   return false;
	 }
	 private function valid_numbers($string)
	 {
	   if(preg_match(self::NUMBERS, $string)) { return true; }
	   return false;
	 }
	 private function valid_letters_numbers($string)
	 {
	 	if(preg_match(self::LETTERS_NUMBERS, $string)) { return true; }
	    return false;
	 }
	 private function valid_text($string)
	 {
	   if(preg_match(self::TEXT, $string)) { return true; }
	   return false;
	 }
	 private function valid_length($string, $minlength, $maxlength)
	 {
	   if(strlen($string >=$minlength && $string <=$maxlength)) { return true; }
	   return false;
	 }
	 private function valid_number_range($number, $min, $max)
	 {
	   if($number>=$min && $number <=$max) { return true; }
	   return false;
	 }
	 private function valid_date($string)
	 {
	   if(preg_match(self::DATE, $string)) { return true; }
	   return false;
	 }
	 private function valid_email($string)
	 {
	   if(preg_match(self::EMAIL, $string)) { return true; }
	   return false;
	 }
	 
 }
?>
