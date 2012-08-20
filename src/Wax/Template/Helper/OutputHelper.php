<?php
namespace Wax\Template\Helper;


class OutputHelper extends Helper {
  
  
  
  public function error_messages_for($object) {
    if($object instanceof WaxForm) {
      if($object->bound_to_model) {
        if($object->bound_to_model->errors) $html = "<ul class='user_errors'>";
        foreach($object->bound_to_model->errors as $err=>$mess) {
          $html.="<li>".$mess[0];
        }
      } else {
        foreach($object->elements as $el) {
          foreach($el->errors as $er) $html.= sprintf($er->error_template, $er);
        }
      }
      $html.="</ul>";
      return $html;
    }
    if(strpos($object, "_")) {
      $object = camelize($object, 1);
    }
		$class= new $object;
		$errors = $class->get_errors();
		foreach($errors as $error) {
			$html.= $this->content_tag("li", Inflections::humanize($error['field'])." ".$error['message'], array("class"=>"user_error"));
		}
		if(count($errors)>0) return $this->content_tag("ul", $html, array("class"=>"user_errors"));
		return false;
	}
	
	
	public function error_messages() {
    $errors = Session::get('user_errors');
		if(empty($errors)) {
      return false;
    }
	
		foreach($errors as $error) {
			$html.= $this->content_tag("li", $error, array("class"=>"user_error"));
		}
		if(count($errors)>0) {
			Session::unset_var('user_errors');
			return $this->content_tag("ul", $html, array("class"=>"user_errors"));
		}
		return false;
	}
	
	public function info_messages() {
	  if($messages = Session::get('user_messages')) {
		  foreach($messages as $message) {
			  $html.= $this->content_tag("li", $message, array("class"=>"user_message"));
		  }
		  Session::unset_var('user_messages');
		  return $this->content_tag("ul", $html, array("class"=>"user_messages", "id"=>"user_message_box"));
	  }
		return false;
	}
	

	
	public function paginate_links($recordset, $window = "1", $prev_content="&laquo;", $next_content="&raquo;", $param="page", $prev_content_disabled="&laquo;", $next_content_disabled="&raquo;") {
    if(!$recordset instanceof WXPaginatedRecordset && !$recordset instanceof WaxPaginatedRecordset) return false;
		if($recordset->total_pages <=1) return false;
    $content = "";
    $page = 1; $links = array();
    if($prev_content && !$recordset->is_current($page)) $links[]=link_to($prev_content, $this->paginate_url($param, $recordset->previous_page()));
      else $links[] = $this->content_tag("span", $prev_content_disabled, array("class"=>"disabled"));
    if(!$recordset->is_current($page)) $links[] = link_to($page, $this->paginate_url($param,$page));
    else $links[] = $this->content_tag("span", $page, array("class"=>"disabled current"));
    if($recordset->total_pages > ($window*2)+1 && $recordset->current_page-$window > 2 ) $links[]="<span>&#8230;.</span>";
    if($recordset->total_pages < ($window*2)+1) {
      $win_start = 2; $win_end = $recordset->total_pages - 1;
    } elseif($recordset->current_page <= $window) {
      $win_start = 2; $win_end = $window*2 +1;
    } elseif($recordset->current_page - $window < 2) {
      $win_start = 2; $win_end = $window + 3;
    } elseif($recordset->current_page + $window >=$recordset->total_pages) {
      $win_start = $recordset->total_pages - ($window*2+1); $win_end = $recordset->total_pages -1;
    } else { 
      $win_start = $recordset->current_page - $window;
      $win_end = $recordset->current_page + $window;
    }
    if($win_start <= 1) $win_start=2;
    if($win_end >= $recordset->total_pages) $win_end=$recordset->total_pages-1;
    for($i=$win_start; $i <= $win_end; $i++) {
      if(!$recordset->is_current($i)) $links[] = link_to($i, $this->paginate_url($param,$i));
      else $links[] = $this->content_tag("span", $i, array("class"=>"disabled current"));
    }
    if($recordset->total_pages- $recordset->current_page-1 > $window) $links[]="<span>&#8230;.</span>";
    if(!$recordset->is_current($recordset->total_pages)) $links[] = link_to($recordset->total_pages, $this->paginate_url($param,$recordset->total_pages));
    else $links[] = $this->content_tag("span", $recordset->total_pages, array("class"=>"disabled current"));
    if($next_content && !$recordset->is_last($recordset->current_page)) $links[]=link_to($next_content, $this->paginate_url($param,$recordset->next_page()));
      else $links[] = $this->content_tag("span", $next_content_disabled, array("class"=>"disabled"));
    
    
    foreach($links as $link) $content.= $this->content_tag("li", $link, array("class"=>"pagination_link"));
    return $this->content_tag("ul", $content, array("class"=>"pagination clearfix"));
  }
  
 	public function paginate_url($param, $page) {
    $vals = $_GET;
    $url_base = "/".$vals["route"];
    unset($vals["route"], $vals['no-wax-cache'], $vals['preview']);
    $vals[$param]= $page;
    return $url_base."?".http_build_query($vals, false, "&");
  }
  
}


Wax::register_helper_methods("OutputHelper", array("error_messages_for","error_messages","info_messages","paginate_links","paginate_url"));
