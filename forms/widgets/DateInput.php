<?php

/**
 * Date Input Widget class
 *
 * @package PHP-Wax
 **/
class DateInput extends TextInput {

  public $type="text";
  public $class = "input_field text_field date_field form_datepicker";
  
  public $label_template = "<label for='%s'>%s</label>
                    <script src='http://www.google.com/jsapi'  type='text/javascript'></script>
                    <script type='text/javascript'>                      
                      google.load('jquery', '1.3');
                      google.load('jqueryui', '1.7');
                      $(document).ready(function(){
                      $('input.form_datepicker').datepicker({ dateFormat: 'yy-mm-dd',changeMonth:true,changeYear:true, yearRange: '1945:2020'});
                      });
                    </script>	";


} // END class
?>