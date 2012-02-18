<?php
namespace Wax\Tests;
use Wax\Template\Helper\Inflections;


class InflectionsTest extends WaxTestCase 
{
    public function setUp() {}
    
    public function tearDown() {}
        
    public function test_camelize() {
      $input = "an_underscored_word";
      $this->assertEquals(Inflections::camelize($input), "anUnderscoredWord");
      $this->assertEquals(Inflections::camelize($input, true), "AnUnderscoredWord");
    }

    public function test_capitalize() {
      $input = "a lowercase word";
      $this->assertEquals(Inflections::capitalize($input), "A Lowercase Word");
    }

    public function test_dasherize() {
      $input = "a spaced word";
      $input2 = "an_underscored_word";
      $this->assertEquals(Inflections::dasherize($input), "a-spaced-word");
      $this->assertEquals(Inflections::dasherize($input2), "an-underscored-word");
    }

    public function test_humanize() {
      $input = "an_underscored_word";
      $input2 = "a-dashed-word";
      $this->assertEquals(Inflections::humanize($input), "An underscored word");
      $this->assertEquals(Inflections::humanize($input2), "A dashed word");
    }

    public function test_underscore() {
      $input = "CamelCaseWord";
      $input2 = "a-dashed-word";
      $input3 = "A Spaced Word";
      $this->assertEquals(Inflections::underscore($input), "camel_case_word");
      $this->assertEquals(Inflections::underscore($input2), "a_dashed_word");
      $this->assertEquals(Inflections::underscore($input3), "a_spaced_word");
    }

    public function test_slashify() {
      $input = "CamelCaseWord";
      $this->assertEquals(Inflections::slashify($input), "camel/case/word");
    }

    public function test_slashcamelize() {
      $input = "camel/case/word";
      $this->assertEquals(Inflections::slashcamelize($input, true), "CamelCaseWord");
    }

}

?>