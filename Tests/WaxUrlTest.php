<?php
namespace Wax\Tests;
use Wax\Dispatch\Controller;


/** Mock Classes To Allow Url Testing **/
class BlogController extends Controller {}
class AdminContentController extends Controller {}

class WaxUrlTest extends WaxTestCase {
  public static $orig_mappings = false;
  public static $setup = false;
  
    public function setUp() {
      if(!self::$setup) {self::$orig_mappings = WaxUrl::$mappings; self::$setup=true;}
      WaxUrl::$mappings = self::$orig_mappings;
      WaxUrl::$params = false;
      WaxUrl::$mapped=false;
      WaxUrl::$uri=false;
    }
    
    public function tearDown() {

    }
    
    public function test_basic_map() {
      $_GET["route"]="page/myaction/myid";
      $this->assertEquals(WaxUrl::get("controller"), "page");
      $this->assertEquals(WaxUrl::get("action"), "myaction");
      $this->assertEquals(WaxUrl::get("id"), "myid");
    }
    
    public function test_partial_basic() {
      $_GET["route"]="page/myaction";
      $this->assertEquals(WaxUrl::get("controller"), "page");
      $this->assertEquals(WaxUrl::get("action"), "myaction");
    }
    
    public function test_partial_basic2() {
      $_GET["route"]="page";
      $this->assertEquals(WaxUrl::get("controller"), "page");
    }
    
    public function test_default_map() {
      $_GET["route"]="";
      $this->assertEquals(WaxUrl::get("controller"), "page");
    }
    
    public function test_pattern_map() {
      $_GET["route"]="blog/tech/5";
      WaxUrl::map("blog/:category/:id", array("controller"=>"blog", "action"=>"show"));
      $this->assertEquals(WaxUrl::get("controller"), "blog");
      $this->assertEquals(WaxUrl::get("category"), "tech");
      $this->assertEquals(WaxUrl::get("action"), "show");
      $this->assertEquals(WaxUrl::get("id"), "5");      
    }
    
    public function test_default_pattern_map() {
      $_GET["route"]="page/tech/5";
      WaxUrl::map("page/:category/:id", array("controller"=>"blog", "action"=>"page"));
      $this->assertEquals(WaxUrl::get("controller"), "blog");
      $this->assertEquals(WaxUrl::get("action"), "page");
      $this->assertEquals(WaxUrl::get("category"), "tech");
      $this->assertEquals(WaxUrl::get("id"), "5");
    }
    
    public function test_wildcard_map() {
      $_GET["route"]="article/tech/humour/pics";
      WaxUrl::map("article/:tags*", array("controller"=>"blog", "action"=>"tags"));
      $this->assertTrue(is_array(WaxUrl::get("tags")) );
      $this->assertEquals(count(WaxUrl::get("tags")), 3);
    }
    
    public function test_nested_controller() {
      $_GET["route"]="admin/content";
      $this->assertEquals(WaxUrl::get("controller"), "admin/content");     
    }
    
    public function test_formats() {
      $_GET["route"]="sitemap.xml";
      $this->assertEquals(WaxUrl::get("controller"), "page");
      $this->assertEquals(WaxUrl::get("action"), "sitemap");    
      $this->assertEquals(WaxUrl::get("format"), "xml");    
    }
    
    public function test_defaults() {
      $_GET["route"]="contact";
      $this->assertEquals(WaxUrl::get("controller"), "page");
      $this->assertEquals(WaxUrl::get("action"), "contact");
    }
    
    public function test_defaults2() {
      $_GET["route"]="page";
      $this->assertEquals(WaxUrl::get("controller"), "page");
      $this->assertEquals(WaxUrl::get("action"), "index");
    }
    
    public function test_partial_default() {
      $_GET["route"]="gallery/anyid";
      $this->assertEquals(WaxUrl::get("controller"), "page");
      $this->assertEquals(WaxUrl::get("action"), "gallery");
      $this->assertEquals(WaxUrl::get("id"), "anyid");
    }
    
    public function test_hyphenated_actions() {
      $_GET["route"]="gallery-create/anyid";
      $this->assertEquals(WaxUrl::get("controller"), "page");
      $this->assertEquals(WaxUrl::get("action"), "gallery-create");
      $this->assertEquals(WaxUrl::get("id"), "anyid");
    }
    
    public function test_non_sequential_controller() {
      $_GET["route"]="/en/page/index/45";
      WaxUrl::map(":language/:controller/:action/:id");
      $this->assertEquals(WaxUrl::get("controller"), "page");
      $this->assertEquals(WaxUrl::get("action"), "index");
      $this->assertEquals(WaxUrl::get("id"), "45");
      $this->assertEquals(WaxUrl::get("language"), "en");
    }
    
   
}







