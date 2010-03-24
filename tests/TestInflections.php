<?php

class TestInflections extends WXTestCase 
{
    public function setUp() {}
    
    public function tearDown() {}
        
    public function test_camelize() {
      $input = "an_underscored_word";
      $this->assertEqual(Inflections::camelize($input), "anUnderscoredWord");
      $this->assertEqual(Inflections::camelize($input, true), "AnUnderscoredWord");
    }

    public function test_capitalize() {
      $input = "a lowercase word";
      $this->assertEqual(Inflections::capitalize($input), "A Lowercase Word");
    }

    public function test_dasherize() {
      $input = "a spaced word";
      $input2 = "an_underscored_word";
      $this->assertEqual(Inflections::dasherize($input), "a-spaced-word");
      $this->assertEqual(Inflections::dasherize($input2), "an-underscored-word");
    }

    public function test_humanize() {
      $input = "an_underscored_word";
      $input2 = "a-dashed-word";
      $this->assertEqual(Inflections::humanize($input), "An underscored word");
      $this->assertEqual(Inflections::humanize($input2), "A dashed word");
    }

    public function test_underscore() {
      $input = "CamelCaseWord";
      $input2 = "a-dashed-word";
      $input3 = "A Spaced Word";
      $this->assertEqual(Inflections::underscore($input), "camel_case_word");
      $this->assertEqual(Inflections::underscore($input2), "a_dashed_word");
      $this->assertEqual(Inflections::underscore($input3), "a_spaced_word");
    }

    public function test_slashify() {
      $input = "CamelCaseWord";
      $this->assertEqual(Inflections::slashify($input), "camel/case/word");
    }

    public function test_slashcamelize($slash_word, $upper_first=false) {
      $input = "camel/case/word";
      $this->assertEqual(Inflections::slashcamelize($input, true), "CamelCaseWord");
    }

}

?>