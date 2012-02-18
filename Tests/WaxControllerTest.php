<?php
namespace Wax\Tests;
use Wax\Dispatch\Controller;


class TestController extends Controller {}

class WaxControllerTest extends WaxTestCase 
{
    public function setUp() {
      $this->cont = new TestController();
    }
    
    public function tearDown() {}
        
    
}

