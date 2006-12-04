<?php
/*
 * @package wxframework.core
 *
 * This class is based in part on the helpers functionality in the PHP on Trax Framework. 
 * For more information, see:
 *  http://phpontrax.com/
 */

/**
 * All the countries included in the country_options output.
 */
if(!array_key_exists('COUNTRIES',$GLOBALS)) {
    $GLOBALS['COUNTRIES'] = 
        array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", 
        "Antarctica", "Antigua And Barbuda", "Argentina", "Armenia", "Aruba", "Australia", 
        "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", 
        "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", 
        "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", 
        "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burma", "Burundi", "Cambodia", 
        "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", 
        "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", 
        "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", 
        "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", 
        "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", 
        "El Salvador", "England", "Equatorial Guinea", "Eritrea", "Espana", "Estonia", 
        "Ethiopia", "Falkland Islands", "Faroe Islands", "Fiji", "Finland", "France", 
        "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", 
        "Georgia", "Germany", "Ghana", "Gibraltar", "Great Britain", "Greece", "Greenland", 
        "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", 
        "Haiti", "Heard and Mc Donald Islands", "Honduras", "Hong Kong", "Hungary", "Iceland", 
        "India", "Indonesia", "Ireland", "Israel", "Italy", "Iran", "Iraq", "Jamaica", "Japan", "Jordan", 
        "Kazakhstan", "Kenya", "Kiribati", "Korea, Republic of", "Korea (South)", "Kuwait", 
        "Kyrgyzstan", "Lao People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", 
        "Liberia", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia", 
        "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", 
        "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", 
        "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", 
        "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", 
        "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", 
        "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Ireland", 
        "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", 
        "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", 
        "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russia", "Rwanda", 
        "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", 
        "Samoa (Indep}ent)", "San Marino", "Sao Tome and Principe", "Saudi Arabia", 
        "Scotland", "Senegal", "Serbia and Montenegro", "Seychelles", "Sierra Leone", "Singapore", 
        "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", 
        "South Georgia and the South Sandwich Islands", "South Korea", "Spain", "Sri Lanka", 
        "St. Helena", "St. Pierre and Miquelon", "Suriname", "Svalbard and Jan Mayen Islands", 
        "Swaziland", "Sweden", "Switzerland", "Taiwan", "Tajikistan", "Tanzania", "Thailand", 
        "Togo", "Tokelau", "Tonga", "Trinidad", "Trinidad and Tobago", "Tunisia", "Turkey", 
        "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", 
        "United Arab Emirates", "United Kingdom", "United States", 
        "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", 
        "Vatican City State (Holy See)", "Venezuela", "Viet Nam", "Virgin Islands (British)", 
        "Virgin Islands (U.S.)", "Wales", "Wallis and Futuna Islands", "Western Sahara", 
        "Yemen", "Zambia", "Zimbabwe");
}

/**
 *  @todo Document this class
 */
class FormOptionsHelper extends FormHelper {
    

    public function options_for_select($choices, $selected = null) {
        $options = array();
        if(is_array($choices)) {
            foreach($choices as $choice_value => $choice_text) {
                if(!empty($choice_value)) {
                    $is_selected = ($choice_value == $selected)
                        ? true : false;   
                } else {
                    $is_selected = ($choice_text == $selected)
                        ? true : false;        
                }
                if($is_selected) {
                    $options[] = "<option value=\""
		      . htmlspecialchars($choice_value)
		      . "\" selected=\"selected\">"
		      . htmlspecialchars($choice_text)."</option>";
                } else {
                    $options[] = "<option value=\""
		      . htmlspecialchars($choice_value)
		      . "\">"
		      . htmlspecialchars($choice_text)."</option>";
                }                        
            }    
        }
        return implode("\n", $options);
    }
    
   
    
    /**
     *  @todo Document this method
     *  @uses add_default_name_and_id_and_value()
     *  @uses add_options()
     *  @uses content_tag()
     *  @uses value()
     */
  public function select($obj, $att, $choices, $options=array(), $with_label=true) {         
		$this->initialise($obj, $att);
  	$content = $this->options_for_select($choices, $this->object->{$this->attribute_name});
    unset($options['value']);
    if(!$options["class"]) $options["class"]="input_field select_field";
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
	  $options['id']    = $this->object_name . "_" . $this->attribute_name;
    if($with_label) $html.= $this->make_label($with_label);
    $html.= $this->content_tag("select",$content,$options);
    return $html;           
  }
  

	public function country_select($obj, $att, $options = array(), $with_label=true) {
		$this->initialise($obj, $att);
		if(!$options["class"]) $options["class"]="input_field select_field";
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
	  $options['id']    = $this->object_name . "_" . $this->attribute_name;
	  if($with_label) $html.= $this->make_label($with_label);
	  $html.= $this->to_select_tag($GLOBALS['COUNTRIES'], $options);
		return $html;
	}
  
  public function options_from_collection($collection, $attribute_value, $attribute_text, $blank=null) {
    if(is_array($collection)) {
      if($blank) $array[0]=$blank;
      foreach($collection as $object) {
        $array[$object->{$attribute_value}] = $object->{$attribute_text};            
      }    
    }
    return $array;
  }
  
  public function date_select($obj, $att, $options = array(), $with_label=true) {
    $this->initialise($obj, $att);
	  $shared_id = $this->object_name."_".$this->attribute_name;
    for($i = 1; $i<=31; $i++) {
      $day[$i]=$i;
    }
    for($i = 1; $i<=12; $i++) {
      $month[$i]=$i;
    }
    for($i = 1900; $i<=2020; $i++) {
      $year[$i]=$i;
    }
    $day_options = FormOptionsHelper::options_for_select($day);
    $month_options = FormOptionsHelper::options_for_select($month);
    $year_options = FormOptionsHelper::options_for_select($year);
    $output = javascript_tag("function {$shared_id}_set_date() { 
      $('$shared_id').value = $('{$shared_id}_year').value + '-' + $('{$shared_id}_month').value + '-' + $('{$shared_id}_day').value;
      alert($('$shared_id').value);
    }");
    
    $output .= $this->content_tag("select", $day_options, array("id"=>$shared_id."_day", "onchange"=>"{$shared_id}_set_date();"));
    $output .= $this->content_tag("select", $month_options, array("id"=>$shared_id."_month", "onchange"=>"{$shared_id}_set_date();"));
    $output .= $this->content_tag("select", $year_options, array("id"=>$shared_id."_year", "onchange"=>"{$shared_id}_set_date();"));
    $output .= $this->hidden_field($obj, $att);
    return $output;
  }
  
  

  
}



?>