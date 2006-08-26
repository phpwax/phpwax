function valid_number(string)
{
  var FMregex=/^[0-9\s]*$/;
  if(FMregex.test(string)) return true;
  else return false;
}
function valid_letters(string)
{
  var FMregex=/^[A-Za-z\s]*$/;
  if(FMregex.test(string)) return true;
  else return false;
}
function valid_letters_numbers(string)
{
  var FMregex=/^[A-Za-z0-9\s]*$/;
  if(FMregex.test(string)) return true;
  else return false;
}
function valid_range(string, minlength, maxlength)
{
  if(string >= minlength && string <= maxlength) return true;
  else return false;
}
function valid_length(string, minlength, maxlength)
{
  if(string.length >= minlength && string.length <= maxlength) return true;
  else return false;
}
function valid_present(fieldvalue)
{
  if(fieldvalue.length > 0) return true;
  else return false;
}
function valid_text(string)
{
  var FMregex=/^[A-Za-z0-9\s\w-\'\"]*$/;
  if(FMregex.test(string)) return true;
  else return false;
}
function valid_email(string)
{
  var FMregex=/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  if(FMregex.test(string)) return true;
  else return false;
}
function valid_date(string)
{
  var FMregex=/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/;
  if(FMregex.test(string)) return true;
  else return false;
}
function clear_error(node)
{
 node.style.border='';
 container=node.parentNode;
 for(zz=0; zz<container.childNodes.length; zz++)
  {
   thisnode=container.childNodes[zz];
   if(thisnode.className=='jsAutoError') container.removeChild(thisnode);
  }
}

function jsValidate(formname)
{
   validation_errors=new Array();
   var allNodes = Form.getElements(formname);
   var i;
   var ii;
   for(i = 0; i < allNodes.length; i++) 
   {
	if(allNodes[i].className.length>3)
	  {
	    
	    var wholeclass=allNodes[i].className;
	    var theclasses=new Array();
	    theclasses=wholeclass.split(" ");
	    for(ii=0; ii<theclasses.length; ii++)
	    {
	      var tagsname=allNodes[i].name;
	      var FMregex=/^FM.*$/
	        if(FMregex.test(theclasses[ii]))
	          {
	           classparts=theclasses[ii].split('-');
		       switch(classparts[0])
		       {
		       case 'FMrequired':
		          valid=valid_present(allNodes[i].value);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Required Field'}); clear_error(allNodes[i]);
		       break
		       case 'FMletters':
		          valid=valid_letters(allNodes[i].value);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Only Letters Allowed'}); clear_error(allNodes[i]);
		       break
		       case 'FMnumbers':
		          valid=valid_numbers(allNodes[i].value);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Only Numbers Allowed'}); clear_error(allNodes[i]);
		       break
		       case 'FMtext':
		          valid=valid_text(allNodes[i].value);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Only Text Allowed'}); clear_error(allNodes[i]);
		       break
		       case 'FMemail':
		          valid=valid_email(allNodes[i].value);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Not a Valid Email Address'}); clear_error(allNodes[i]);
		       break
		       case 'FMminlength':
		          minlength=classparts[1]; maxlength=100000;
		          valid=valid_length(allNodes[i].value, minlength, maxlength);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Not Sufficient Characters'}); clear_error(allNodes[i]);
		       break
		       case 'FMmaxlength':
		          maxlength=classparts[1]; minlength=0;
		          valid=valid_length(allNodes[i].value, minlength, maxlength);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Too Many Characters'}); clear_error(allNodes[i]);
		       break
		       case 'FMnumberrange':
		          minlength=classparts[1]; maxlength=classparts[2];
		          valid=valid_range(allNodes[i].value, minlength, maxlength);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Too Many Characters'}); clear_error(allNodes[i]);
		       break
		       case 'FMdate':
		          valid=valid_date(allNodes[i].value);
		          if(!valid) validation_errors.push({'field':allNodes[i], 'type':'Wrong date format (dd/mm/yyyy)'}); clear_error(allNodes[i]);
		       break
		       }
			     
				   
	               
	               
	         }
	    }
	  }
   }
   if(validation_errors.length>0) 
     { 
       error_message='';
       for(i=0; i<validation_errors.length; i++)
       {
        thefield=validation_errors[i]['field'];
        thefield.style.border='1px solid red';
        var tmp = document.createElement('strong');
        tmp.className='jsAutoError'
		tmp.innerHTML = validation_errors[i]['type'];
		tmp.style.color='red';
		theparent=thefield.parentNode
		theparent.appendChild(tmp);
        
       }
     error_message+='There were '+i+' errors in your form submission';
     window.alert(error_message);
     return false;
     }
   else return true;
   
}