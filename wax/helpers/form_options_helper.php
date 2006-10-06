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
    
    /**
     *  Generate HTML option tags from a list of choices
     *
     *  Accepts an array of possible choices and returns a string of
     *  option tags.  The value of each array element becomes the
     *  visible text of an option, and the key of the element becomes
     *  the value returned to the server.  For example:<br />
     *  <samp>options_for_select(array('foo','bar'));</samp><br />
     *  will return:<br />
     *  <samp><option value="0">foo</option>\n</samp><br />
     *  <samp><option value="1">bar</option></samp><br />
     *
     *  The optional second argument specifies the array key of an
     *  option to be initially selected.
     * 
     *  NOTE: Only the option tags are returned, you have to wrap this
     *  call in a regular HTML select tag.
     *  @param string[]  Choices
     *  @param integer   Selected choice
     *  @return string
     */
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
    function to_select_tag($choices, $options) 
    {           
        $options = $this->add_default_name_and_id_and_value($options);
        $content = $this->options_for_select($choices, $this->get_value());
        unset($options['value']);
        return $this->content_tag("select",$content,$options);           
    }  

  
}

/**
 *  Create a new FormOptionsHelper object and call its to_select_tag() method
 *
 *  Create a select tag and a series of contained option tags for the
 *  provided object and method.  The option currently held by the
 *  object will be selected, provided that the object is available. 
 *  See options_for_select for the required format of the choices parameter.
 *
 * Example with $post->person_id => 1:
 *   $person = new Person;
 *   $people = $person->find_all();
 *   foreach($people as $person) {
 *      $choices[$person->id] = $person->first_name;
 *   }
 *   select("post", "person_id", $choices, array("include_blank" => true))
 *
 * could become:
 *
 *   <select name="post[person_id]">
 *     <option></option>
 *     <option value="1" selected="selected">David</option>
 *     <option value="2">Sam</option>
 *     <option value="3">Tobias</option>
 *   </select>
 *
 *  This can be used to provide a functionault set of options in the
 *  standard way: before r}ering the create form, a new model instance
 *  is assigned the functional options and bound to
 *  @model_name. Usually this model is not saved to the
 *  database. Instead, a second model object is created when the
 *  create request is received.  This allows the user to submit a form
 *  page more than once with the expected results of creating multiple
 *  records.  In addition, this allows a single partial to be used to
 *  generate form inputs for both edit and create forms. 
 *  @todo Document this function
 */
function select($object, $field, $choices, $options = array()) 
{
    $form             = new FormOptionsHelper($object, $field);
    $options['name']  = $object . "[" . $field . "]" ;
    $options['id']    = $object . "_" . $field;     
    return $form->to_select_tag($choices, $options);
}

function label_select($object, $field, $choices, $options = array(), $label_name="") 
{
    
  return make_label($object, $field, $label_name) . select($object, $field, $choices, $options) ; 
}
/**
  * no object name used, can be with or without a label - default is without (false)
  * options is now required - in particular the name param
  * if the name param is missing an exception is thrown
  */
function no_obj_select($options, $choices, $label_name="")
{
  if(empty($options['name']))
  {
    throw new WXException("Incorrect Formatting - 'name' is a required attribute");  
  }
  $name = $options['name'];
 
  $form = new FormHelper("", $name);
  if(!$label)
  {
    return $form->to_select_tag($choices, $options);
  }
  else
  {
    return make_label("", $options['name'], "", "") . $form->to_select_tag($choices, $options);
  }    
}


/**
 *  Create a new FormOptionsHelper object and call its to_country_select_tag() method
 *
 * Return select and option tags for the given object and method, using country_options_for_select to generate the list of option tags.
 *  @todo Document this function
 *  @uses FormOptionsHelper::country_select()
 */
function country_select($object, $field, $options = array())  
{
    $form             = new FormOptionsHelper($object, $field);
    $options['name']  = $object . "[" . $field . "]" ;
    $options['id']    = $object . "_" . $field;     
    return $form->to_select_tag($GLOBALS['COUNTRIES'], $options);
}

function label_country_select($object, $field, $options = array(), $label_name="") 
{
    
  return make_label($object, $field, $label_name) . country_select($object, $field, $options) ; 
}


/**
  * Alternative version - no object name used, can be with or 
  * without a label - default is without (false)
  * options is now required - in particular the name param
  */
function no_obj_country_select($options, $label_name="")
{
  if(empty($options['name']))
  {
    throw new WXException("Incorrect Formatting - 'name' is a required attribute");  
  }
  $name = $options['name'];
 
  $form = new FormHelper("", $name);
  if(!$label)
  {
    return $form->to_select_tag($GLOBALS['COUNTRIES'], $options);
  }
  else
  {
    return make_label("", $options['name'], "", "") . $form->to_select_tag($GLOBALS['COUNTRIES'], $options);
  }    
}

// -- set Emacs parameters --
// Local variables:
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>