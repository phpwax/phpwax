/**
  *
  *  Copyright 2005 Sabre Airline Solutions
  *
  *  Licensed under the Apache License, Version 2.0 (the "License"); you may not use this
  *  file except in compliance with the License. You may obtain a copy of the License at
  *
  *         http://www.apache.org/licenses/LICENSE-2.0
  *
  *  Unless required by applicable law or agreed to in writing, software distributed under the
  *  License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
  *  either express or implied. See the License for the specific language governing permissions
  *  and limitations under the License.
  **/


//-------------------- rico.js
var Rico = {
  Version: '1.1.2',
  prototypeVersion: parseFloat(Prototype.Version.split(".")[0] + "." + Prototype.Version.split(".")[1])
}

if((typeof Prototype=='undefined') || Rico.prototypeVersion < 1.3)
      throw("Rico requires the Prototype JavaScript framework >= 1.3");

Rico.ArrayExtensions = new Array();

if (Object.prototype.extend) {
   Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Object.prototype.extend;
}else{
  Object.prototype.extend = function(object) {
    return Object.extend.apply(this, [this, object]);
  }
  Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Object.prototype.extend;
}

if (Array.prototype.push) {
   Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Array.prototype.push;
}

if (!Array.prototype.remove) {
   Array.prototype.remove = function(dx) {
      if( isNaN(dx) || dx > this.length )
         return false;
      for( var i=0,n=0; i<this.length; i++ )
         if( i != dx )
            this[n++]=this[i];
      this.length-=1;
   };
  Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Array.prototype.remove;
}

if (!Array.prototype.removeItem) {
   Array.prototype.removeItem = function(item) {
      for ( var i = 0 ; i < this.length ; i++ )
         if ( this[i] == item ) {
            this.remove(i);
            break;
         }
   };
  Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Array.prototype.removeItem;
}

if (!Array.prototype.indices) {
   Array.prototype.indices = function() {
      var indexArray = new Array();
      for ( index in this ) {
         var ignoreThis = false;
         for ( var i = 0 ; i < Rico.ArrayExtensions.length ; i++ ) {
            if ( this[index] == Rico.ArrayExtensions[i] ) {
               ignoreThis = true;
               break;
            }
         }
         if ( !ignoreThis )
            indexArray[ indexArray.length ] = index;
      }
      return indexArray;
   }
  Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Array.prototype.indices;
}

// Create the loadXML method and xml getter for Mozilla
if ( window.DOMParser &&
	  window.XMLSerializer &&
	  window.Node && Node.prototype && Node.prototype.__defineGetter__ ) {

   if (!Document.prototype.loadXML) {
      Document.prototype.loadXML = function (s) {
         var doc2 = (new DOMParser()).parseFromString(s, "text/xml");
         while (this.hasChildNodes())
            this.removeChild(this.lastChild);

         for (var i = 0; i < doc2.childNodes.length; i++) {
            this.appendChild(this.importNode(doc2.childNodes[i], true));
         }
      };
	}

	Document.prototype.__defineGetter__( "xml",
	   function () {
		   return (new XMLSerializer()).serializeToString(this);
	   }
	 );
}

document.getElementsByTagAndClassName = function(tagName, className) {
  if ( tagName == null )
     tagName = '*';

  var children = document.getElementsByTagName(tagName) || document.all;
  var elements = new Array();

  if ( className == null )
    return children;

  for (var i = 0; i < children.length; i++) {
    var child = children[i];
    var classNames = child.className.split(' ');
    for (var j = 0; j < classNames.length; j++) {
      if (classNames[j] == className) {
        elements.push(child);
        break;
      }
    }
  }

  return elements;
}


//-------------------- ricoAccordion.js
Rico.Accordion = Class.create();

Rico.Accordion.prototype = {

   initialize: function(container, options) {
      this.container            = $(container);
      this.lastExpandedTab      = null;
      this.accordionTabs        = new Array();
      this.setOptions(options);
      this._attachBehaviors();
      if(!container) return;

      this.container.style.borderBottom = '1px solid ' + this.options.borderColor;
      // validate onloadShowTab
       if (this.options.onLoadShowTab >= this.accordionTabs.length)
        this.options.onLoadShowTab = 0;

      // set the initial visual state...
      for ( var i=0 ; i < this.accordionTabs.length ; i++ )
      {
        if (i != this.options.onLoadShowTab){
         this.accordionTabs[i].collapse();
         this.accordionTabs[i].content.style.display = 'none';
        }
      }
      this.lastExpandedTab = this.accordionTabs[this.options.onLoadShowTab];
      if (this.options.panelHeight == 'auto'){
          var tabToCheck = (this.options.onloadShowTab === 0)? 1 : 0;
          var titleBarSize = parseInt(RicoUtil.getElementsComputedStyle(this.accordionTabs[tabToCheck].titleBar, 'height'));
          if (isNaN(titleBarSize))
            titleBarSize = this.accordionTabs[tabToCheck].titleBar.offsetHeight;
          
          var totalTitleBarSize = this.accordionTabs.length * titleBarSize;
          var parentHeight = parseInt(RicoUtil.getElementsComputedStyle(this.container.parentNode, 'height'));
          if (isNaN(parentHeight))
            parentHeight = this.container.parentNode.offsetHeight;
          
          this.options.panelHeight = parentHeight - totalTitleBarSize-2;
      }
      
      this.lastExpandedTab.content.style.height = this.options.panelHeight + "px";
      this.lastExpandedTab.showExpanded();
      this.lastExpandedTab.titleBar.style.fontWeight = this.options.expandedFontWeight;

   },

   setOptions: function(options) {
      this.options = {
         expandedBg          : '#63699c',
         hoverBg             : '#63699c',
         collapsedBg         : '#6b79a5',
         expandedTextColor   : '#ffffff',
         expandedFontWeight  : 'bold',
         hoverTextColor      : '#ffffff',
         collapsedTextColor  : '#ced7ef',
         collapsedFontWeight : 'normal',
         hoverTextColor      : '#ffffff',
         borderColor         : '#1f669b',
         panelHeight         : 200,
         onHideTab           : null,
         onShowTab           : null,
         onLoadShowTab       : 0
      }
      Object.extend(this.options, options || {});
   },

   showTabByIndex: function( anIndex, animate ) {
      var doAnimate = arguments.length == 1 ? true : animate;
      this.showTab( this.accordionTabs[anIndex], doAnimate );
   },

   showTab: function( accordionTab, animate ) {
     if ( this.lastExpandedTab == accordionTab )
        return;

      var doAnimate = arguments.length == 1 ? true : animate;

      if ( this.options.onHideTab )
         this.options.onHideTab(this.lastExpandedTab);

      this.lastExpandedTab.showCollapsed(); 
      var accordion = this;
      var lastExpandedTab = this.lastExpandedTab;

      this.lastExpandedTab.content.style.height = (this.options.panelHeight - 1) + 'px';
      accordionTab.content.style.display = '';

      accordionTab.titleBar.style.fontWeight = this.options.expandedFontWeight;

      if ( doAnimate ) {
         new Rico.Effect.AccordionSize( this.lastExpandedTab.content,
                                   accordionTab.content,
                                   1,
                                   this.options.panelHeight,
                                   100, 10,
                                   { complete: function() {accordion.showTabDone(lastExpandedTab)} } );
         this.lastExpandedTab = accordionTab;
      }
      else {
         this.lastExpandedTab.content.style.height = "1px";
         accordionTab.content.style.height = this.options.panelHeight + "px";
         this.lastExpandedTab = accordionTab;
         this.showTabDone(lastExpandedTab);
      }
   },

   showTabDone: function(collapsedTab) {
      collapsedTab.content.style.display = 'none';
      this.lastExpandedTab.showExpanded();
      if ( this.options.onShowTab )
         this.options.onShowTab(this.lastExpandedTab);
   },

   _attachBehaviors: function() {
      var panels = this._getDirectChildrenByTag(this.container, 'DIV');
      for ( var i = 0 ; i < panels.length ; i++ ) {

         var tabChildren = this._getDirectChildrenByTag(panels[i],'DIV');
         if ( tabChildren.length != 2 )
            continue; // unexpected

         var tabTitleBar   = tabChildren[0];
         var tabContentBox = tabChildren[1];
         this.accordionTabs.push( new Rico.Accordion.Tab(this,tabTitleBar,tabContentBox) );
      }
   },

   _getDirectChildrenByTag: function(e, tagName) {
      var kids = new Array();
      var allKids = e.childNodes;
      for( var i = 0 ; i < allKids.length ; i++ )
         if ( allKids[i] && allKids[i].tagName && allKids[i].tagName == tagName )
            kids.push(allKids[i]);
      return kids;
   }

};

Rico.Accordion.Tab = Class.create();

Rico.Accordion.Tab.prototype = {

   initialize: function(accordion, titleBar, content) {
      this.accordion = accordion;
      this.titleBar  = titleBar;
      this.content   = content;
      this._attachBehaviors();
   },

   collapse: function() {
      this.showCollapsed();
      this.content.style.height = "1px";
   },

   showCollapsed: function() {
      this.expanded = false;
      this.titleBar.style.backgroundColor = this.accordion.options.collapsedBg;
      this.titleBar.style.color           = this.accordion.options.collapsedTextColor;
      this.titleBar.style.fontWeight      = this.accordion.options.collapsedFontWeight;
      this.content.style.overflow = "hidden";
   },

   showExpanded: function() {
      this.expanded = true;
      this.titleBar.style.backgroundColor = this.accordion.options.expandedBg;
      this.titleBar.style.color           = this.accordion.options.expandedTextColor;
      this.content.style.overflow         = "auto";
   },

   titleBarClicked: function(e) {
      if ( this.accordion.lastExpandedTab == this )
         return;
      this.accordion.showTab(this);
   },

   hover: function(e) {
		this.titleBar.style.backgroundColor = this.accordion.options.hoverBg;
		this.titleBar.style.color           = this.accordion.options.hoverTextColor;
   },

   unhover: function(e) {
      if ( this.expanded ) {
         this.titleBar.style.backgroundColor = this.accordion.options.expandedBg;
         this.titleBar.style.color           = this.accordion.options.expandedTextColor;
      }
      else {
         this.titleBar.style.backgroundColor = this.accordion.options.collapsedBg;
         this.titleBar.style.color           = this.accordion.options.collapsedTextColor;
      }
   },

   _attachBehaviors: function() {
      this.content.style.border = "1px solid " + this.accordion.options.borderColor;
      this.content.style.borderTopWidth    = "0px";
      this.content.style.borderBottomWidth = "0px";
      this.content.style.margin            = "0px";

      this.titleBar.onclick     = this.titleBarClicked.bindAsEventListener(this);
      this.titleBar.onmouseover = this.hover.bindAsEventListener(this);
      this.titleBar.onmouseout  = this.unhover.bindAsEventListener(this);
   }

};

//-------------------- ricoEffects.js

Rico.Effect = {};

Rico.Effect.SizeAndPosition = Class.create();
Rico.Effect.SizeAndPosition.prototype = {

   initialize: function(element, x, y, w, h, duration, steps, options) {
      this.element = $(element);
      this.x = x;
      this.y = y;
      this.w = w;
      this.h = h;
      this.duration = duration;
      this.steps    = steps;
      this.options  = arguments[7] || {};

      this.sizeAndPosition();
   },

   sizeAndPosition: function() {
      if (this.isFinished()) {
         if(this.options.complete) this.options.complete(this);
         return;
      }

      if (this.timer)
         clearTimeout(this.timer);

      var stepDuration = Math.round(this.duration/this.steps) ;

      // Get original values: x,y = top left corner;  w,h = width height
      var currentX = this.element.offsetLeft;
      var currentY = this.element.offsetTop;
      var currentW = this.element.offsetWidth;
      var currentH = this.element.offsetHeight;

      // If values not set, or zero, we do not modify them, and take original as final as well
      this.x = (this.x) ? this.x : currentX;
      this.y = (this.y) ? this.y : currentY;
      this.w = (this.w) ? this.w : currentW;
      this.h = (this.h) ? this.h : currentH;

      // how much do we need to modify our values for each step?
      var difX = this.steps >  0 ? (this.x - currentX)/this.steps : 0;
      var difY = this.steps >  0 ? (this.y - currentY)/this.steps : 0;
      var difW = this.steps >  0 ? (this.w - currentW)/this.steps : 0;
      var difH = this.steps >  0 ? (this.h - currentH)/this.steps : 0;

      this.moveBy(difX, difY);
      this.resizeBy(difW, difH);

      this.duration -= stepDuration;
      this.steps--;

      this.timer = setTimeout(this.sizeAndPosition.bind(this), stepDuration);
   },

   isFinished: function() {
      return this.steps <= 0;
   },

   moveBy: function( difX, difY ) {
      var currentLeft = this.element.offsetLeft;
      var currentTop  = this.element.offsetTop;
      var intDifX     = parseInt(difX);
      var intDifY     = parseInt(difY);

      var style = this.element.style;
      if ( intDifX != 0 )
         style.left = (currentLeft + intDifX) + "px";
      if ( intDifY != 0 )
         style.top  = (currentTop + intDifY) + "px";
   },

   resizeBy: function( difW, difH ) {
      var currentWidth  = this.element.offsetWidth;
      var currentHeight = this.element.offsetHeight;
      var intDifW       = parseInt(difW);
      var intDifH       = parseInt(difH);

      var style = this.element.style;
      if ( intDifW != 0 )
         style.width   = (currentWidth  + intDifW) + "px";
      if ( intDifH != 0 )
         style.height  = (currentHeight + intDifH) + "px";
   }
}

Rico.Effect.Size = Class.create();
Rico.Effect.Size.prototype = {

   initialize: function(element, w, h, duration, steps, options) {
      new Rico.Effect.SizeAndPosition(element, null, null, w, h, duration, steps, options);
  }
}

Rico.Effect.Position = Class.create();
Rico.Effect.Position.prototype = {

   initialize: function(element, x, y, duration, steps, options) {
      new Rico.Effect.SizeAndPosition(element, x, y, null, null, duration, steps, options);
  }
}

Rico.Effect.Round = Class.create();
Rico.Effect.Round.prototype = {

   initialize: function(tagName, className, options) {
      var elements = document.getElementsByTagAndClassName(tagName,className);
      for ( var i = 0 ; i < elements.length ; i++ )
         Rico.Corner.round( elements[i], options );
   }
};

Rico.Effect.FadeTo = Class.create();
Rico.Effect.FadeTo.prototype = {

   initialize: function( element, opacity, duration, steps, options) {
      this.element  = $(element);
      this.opacity  = opacity;
      this.duration = duration;
      this.steps    = steps;
      this.options  = arguments[4] || {};
      this.fadeTo();
   },

   fadeTo: function() {
      if (this.isFinished()) {
         if(this.options.complete) this.options.complete(this);
         return;
      }

      if (this.timer)
         clearTimeout(this.timer);

      var stepDuration = Math.round(this.duration/this.steps) ;
      var currentOpacity = this.getElementOpacity();
      var delta = this.steps > 0 ? (this.opacity - currentOpacity)/this.steps : 0;

      this.changeOpacityBy(delta);
      this.duration -= stepDuration;
      this.steps--;

      this.timer = setTimeout(this.fadeTo.bind(this), stepDuration);
   },

   changeOpacityBy: function(v) {
      var currentOpacity = this.getElementOpacity();
      var newOpacity = Math.max(0, Math.min(currentOpacity+v, 1));
      this.element.ricoOpacity = newOpacity;

      this.element.style.filter = "alpha(opacity:"+Math.round(newOpacity*100)+")";
      this.element.style.opacity = newOpacity; /*//*/;
   },

   isFinished: function() {
      return this.steps <= 0;
   },

   getElementOpacity: function() {
      if ( this.element.ricoOpacity == undefined ) {
         var opacity = RicoUtil.getElementsComputedStyle(this.element, 'opacity');
         this.element.ricoOpacity = opacity != undefined ? opacity : 1.0;
      }
      return parseFloat(this.element.ricoOpacity);
   }
}

Rico.Effect.AccordionSize = Class.create();

Rico.Effect.AccordionSize.prototype = {

   initialize: function(e1, e2, start, end, duration, steps, options) {
      this.e1       = $(e1);
      this.e2       = $(e2);
      this.start    = start;
      this.end      = end;
      this.duration = duration;
      this.steps    = steps;
      this.options  = arguments[6] || {};

      this.accordionSize();
   },

   accordionSize: function() {

      if (this.isFinished()) {
         // just in case there are round errors or such...
         this.e1.style.height = this.start + "px";
         this.e2.style.height = this.end + "px";

         if(this.options.complete)
            this.options.complete(this);
         return;
      }

      if (this.timer)
         clearTimeout(this.timer);

      var stepDuration = Math.round(this.duration/this.steps) ;

      var diff = this.steps > 0 ? (parseInt(this.e1.offsetHeight) - this.start)/this.steps : 0;
      this.resizeBy(diff);

      this.duration -= stepDuration;
      this.steps--;

      this.timer = setTimeout(this.accordionSize.bind(this), stepDuration);
   },

   isFinished: function() {
      return this.steps <= 0;
   },

   resizeBy: function(diff) {
      var h1Height = this.e1.offsetHeight;
      var h2Height = this.e2.offsetHeight;
      var intDiff = parseInt(diff);
      if ( diff != 0 ) {
         this.e1.style.height = (h1Height - intDiff) + "px";
         this.e2.style.height = (h2Height + intDiff) + "px";
      }
   }

};