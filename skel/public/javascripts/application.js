function add_form_listeners() {
  document.getElementsByClassName("input_field").each(function(num) {
    Event.observe(num, "focus", function(event){ Event.element(event).parentNode.style.backgroundColor="#FCF9AD";});
  });
  document.getElementsByClassName("input_field").each(function(num) {
    Event.observe(num, "blur", function(event){ Event.element(event).parentNode.style.backgroundColor="white";});
  });
}

Event.observe(window, "load", add_form_listeners);
