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
    

    function options_for_select($choices, $selected = null) {
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
    function select($choices, $options=array()) {           
    	$content = $this->options_for_select($choices, $this->object->{$this->attribute_name});
      unset($options['value']);
			$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
		  $options['id']    = $this->object_name . "_" . $this->attribute_name;
      return $this->content_tag("select",$content,$options);           
    }

	function country_select($options = array()) {
		$options['name']  = $this->object_name . "[" . $this->attribute_name . "]" ;
	  $options['id']    = $this->object_name . "_" . $this->attribute_name;
		return $this->to_select_tag($GLOBALS['COUNTRIES'], $options);
	}

  
}


function select()  {
	$args = func_get_args();
	$helper = new FormOptionsHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'select'), $args);
}



/**
 *  Create a new FormOptionsHelper object and call its to_country_select_tag() method
 *
 * Return select and option tags for the given object and method, using country_options_for_select to generate the list of option tags.
 *  @todo Document this function
 *  @uses FormOptionsHelper::country_select()
 */
function country_select($object, $field, $options = array()) {
	$args = func_get_args();
	$helper = new FormOptionsHelper($args[0], $args[1]);
	array_shift($args); array_shift($args);
	return call_user_func_array(array($helper, 'select'), $args);
}




?>