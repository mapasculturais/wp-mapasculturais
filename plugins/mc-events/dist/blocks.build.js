!function(e){function t(o){if(n[o])return n[o].exports;var r=n[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,t),r.l=!0,r.exports}var n={};t.m=e,t.c=n,t.d=function(e,n,o){t.o(e,n)||Object.defineProperty(e,n,{configurable:!1,enumerable:!0,get:o})},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=0)}([function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0});n(1)},function(e,t,n){"use strict";var o=n(2),r=(n.n(o),n(3)),__=(n.n(r),wp.i18n.__);(0,wp.blocks.registerBlockType)("cgb/block-mc-events",{title:__("Eventos - Mapas Culturais"),icon:"shield",category:"common",attributes:{view:{type:"string"}},keywords:[__("evetos \u2014 mapas culturais")],edit:function(e){function t(t){console.log(t.target),e.setAttributes({view:t.target.value})}return wp.element.createElement("div",{className:e.className},wp.element.createElement("select",{name:"view",id:"view",onChange:t},wp.element.createElement("option",{value:"list"},"Visualiza\xe7\xe3o"),wp.element.createElement("option",{value:"agenda"},"Agenda"),wp.element.createElement("option",{value:"calendar"},"Calend\xe1rio"),wp.element.createElement("option",{value:"list"},"Lista"),wp.element.createElement("option",{value:"day"},"Di\xe1rio"),wp.element.createElement("option",{value:"day"},"Agora")))},save:function(e){return console.log(e),wp.element.createElement("mc-w-"+e.attributes.view)}})},function(e,t){},function(e,t){}]);