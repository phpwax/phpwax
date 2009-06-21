CodeHighlighter.addStyle("php",{
	comment : {
		exp  : /#[^\n]*/
	},
	brackets : {
		exp  : /\(|\)|\[|\]|\{|\}/
	},
	string : {
		exp  : /'[^']*'|"[^"]*"/
	},
	keywords : {
		exp  : /\b(end|self|class|function|if|public|private|protected|echo|then|else|for|switch|unless|while|elseif|case|when|break|retry|redo|rescue|require|raise)\b/
	},
	constants : {
	  exp  : /\b(true|false|__[A-Z][^\W]+|[A-Z]\w+)\b/
	},
	symbol : {
	  exp  : /:[^\W]+/
	},
	instance : {
	  exp  : /@+[^\W]+/
	},
	method : {
	  exp  : /[^\w]*\.(\w*)[!?]*/
	}
});