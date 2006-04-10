<?php
    // $Id: reflection_php5_test.php,v 1.7 2005/09/09 02:51:52 lastcraft Exp $

    class AnyOldClass {
        function aMethod() {
        }
    }

    interface AnyOldInterface {
        function aMethod();
    }

    class AnyOldImplementation implements AnyOldInterface {
    	function aMethod() { }
    }

    class AnyOldSubclass extends AnyOldImplementation { }

	class AnyOldArgumentClass {
		function aMethod($argument) { }
	}

	class AnyOldTypeHintedClass {
		function aMethod(SimpleTest $argument) { }
	}


    class TestOfReflection extends UnitTestCase {

        function testClassExistence() {
            $reflection = new SimpleReflection('AnyOldClass');
            $this->assertTrue($reflection->classOrInterfaceExists());
            $this->assertTrue($reflection->classOrInterfaceExistsSansAutoload());
        }

        function testClassNonExistence() {
            $reflection = new SimpleReflection('UnknownThing');
            $this->assertFalse($reflection->classOrInterfaceExists());
            $this->assertFalse($reflection->classOrInterfaceExistsSansAutoload());
        }

        function testInterfaceExistence() {
            $reflection = new SimpleReflection('AnyOldInterface');
            $this->assertTrue(
            		$reflection->classOrInterfaceExists());
            $this->assertTrue(
            		$reflection->classOrInterfaceExistsSansAutoload());
        }

        function testMethodsListFromClass() {
            $reflection = new SimpleReflection('AnyOldClass');
            $methods = $reflection->getMethods();
            $this->assertEqual($methods[0], 'aMethod');
        }

        function testMethodsListFromInterface() {
            $reflection = new SimpleReflection('AnyOldInterface');
            $methods = $reflection->getMethods();
            $this->assertEqual($methods[0], 'aMethod');
        }

        function testInterfaceHasOnlyItselfToImplement() {
            $reflection = new SimpleReflection('AnyOldInterface');
        	$this->assertEqual(
        			$reflection->getInterfaces(),
        			array('AnyOldInterface'));
        }

        function testInterfacesListedForClass() {
            $reflection = new SimpleReflection('AnyOldImplementation');
        	$this->assertEqual(
        			$reflection->getInterfaces(),
        			array('AnyOldInterface'));
        }

        function testInterfacesListedForSubclass() {
            $reflection = new SimpleReflection('AnyOldSubclass');
        	$this->assertEqual(
        			$reflection->getInterfaces(),
        			array('AnyOldInterface'));
        }

		function testParameterCreationWithoutTypeHinting() {
			$reflection = new SimpleReflection('AnyOldArgumentClass');
			$function = $reflection->getSignature('aMethod');
			$this->assertEqual('function aMethod($argument)', $function);
		}

		function testParameterCreationForTypeHinting() {
			$reflection = new SimpleReflection('AnyOldTypeHintedClass');
			$function = $reflection->getSignature('aMethod');
			$this->assertEqual('function aMethod(SimpleTest $argument)', $function);
		}
    }
?>