<?php

class TestInflections extends WXTestCase 
{
    public function setUp() {}
    
    public function tearDown() {}
        
    public function test_camelize() {
      $input = "an_underscored_word";
      $this->assertEqual(WXInflections::camelize($input), "anUnderscoredWord");
      $this->assertEqual(WXInflections::camelize($input, true), "AnUnderscoredWord");
    }

    public function test_capitalize() {
      $input = "a lowercase word";
      $this->assertEqual(WXInflections::capitalize($input), "A Lowercase Word");
    }

    public function test_dasherize() {
      $input = "a spaced word";
      $input2 = "an_underscored_word";
      $this->assertEqual(WXInflections::dasherize($input), "a-spaced-word");
      $this->assertEqual(WXInflections::dasherize($input2), "an-underscored-word");
    }

    public function test_humanize() {
      $input = "an_underscored_word";
      $input2 = "a-dashed-word";
      $this->assertEqual(WXInflections::humanize($input), "An underscored word");
      $this->assertEqual(WXInflections::humanize($input2), "A dashed word");
    }

    public function test_underscore() {
      $input = "CamelCaseWord";
      $input2 = "a-dashed-word";
      $input3 = "A Spaced Word";
      $this->assertEqual(WXInflections::underscore($input), "camel_case_word");
      $this->assertEqual(WXInflections::underscore($input2), "a_dashed_word");
      $this->assertEqual(WXInflections::underscore($input3), "a_spaced_word");
    }

    public function test_slashify() {
      $input = "CamelCaseWord";
      $this->assertEqual(WXInflections::slashify($input), "camel/case/word");
    }

    public function test_slashcamelize($slash_word, $upper_first=false) {
      $input = "camel/case/word";
      $this->assertEqual(WXInflections::slashcamelize($input, true), "CamelCaseWord");
    }

}

?>