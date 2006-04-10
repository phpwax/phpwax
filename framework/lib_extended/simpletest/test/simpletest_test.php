<?php
    // $Id: simpletest_test.php,v 1.2 2005/08/16 03:32:11 lastcraft Exp $
    require_once(dirname(__FILE__) . '/../simpletest.php');
        
    SimpleTest::ignore('ShouldNeverBeRun');
    class ShouldNeverBeRun extends UnitTestCase {
        function testWithNoChanceOfSuccess() {
            $this->fail('Should be ignored');
        }
    }
?>