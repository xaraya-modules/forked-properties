﻿/*
Copyright (c) 2003-2009, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

(function(){var a=1,b=2,c=4,d=8,e=/^\s*(\d+)((px)|\%)?\s*$/i,f=/(^\s*(\d+)((px)|\%)?\s*$)|^$/i,g=function(){var m=this.getValue(),n=this.getDialog(),o=m.match(e);if(o){if(o[2]=='%')i(n,false);m=o[1];}if(n.lockRatio){var p=n.originalElement;if(p.getCustomData('isReady')=='true')if(this.id=='txtHeight'){if(m!=''&&m!=0)m=Math.round(p.$.width*(m/p.$.height));if(!isNaN(m))n.setValueOf('info','txtWidth',m);}else{if(m!=''&&m!=0)m=Math.round(p.$.height*(m/p.$.width));if(!isNaN(m))n.setValueOf('info','txtHeight',m);}}h(n);},h=function(m){if(!m.originalElement||!m.preview)return 1;m.commitContent(c,m.preview);return 0;},i=function(m,n){var o=m.originalElement,p=CKEDITOR.document.getById('btnLockSizes');if(o.getCustomData('isReady')=='true')if(n=='check'){var q=m.getValueOf('info','txtWidth'),r=m.getValueOf('info','txtHeight'),s=o.$.width*1000/o.$.height,t=q*1000/r;m.lockRatio=false;if(q==0&&r==0)m.lockRatio=true;else if(!isNaN(s)&&!isNaN(t)){if(Math.round(s)==Math.round(t))m.lockRatio=true;}}else if(n!=undefined)m.lockRatio=n;else m.lockRatio=!m.lockRatio;else if(n!='check')m.lockRatio=false;if(m.lockRatio)p.removeClass('BtnUnlocked');else p.addClass('BtnUnlocked');return m.lockRatio;},j=function(m){var n=m.originalElement;if(n.getCustomData('isReady')=='true'){m.setValueOf('info','txtWidth',n.$.width);m.setValueOf('info','txtHeight',n.$.height);}h(m);},k=function(m,n){if(m!=a)return 0;var o=function(t,u){var v=t.match(e);if(v){if(v[2]=='%'){v[1]+='%';i(p,false);}return v[1];}return u;},p=this.getDialog(),q='',r=this.id=='txtWidth'?'width':'height',s=n.getAttribute(r);if(s)q=o(s,q);q=o(n.$.style[r],q);this.setValue(q);},l=function(m,n){var o=function(){var r=this;var q=r.originalElement;q.setCustomData('isReady','true');q.removeListener('load',o);q.removeListener('error',p);q.removeListener('abort',p);CKEDITOR.document.getById('ImagePreviewLoader').setStyle('display','none');if(r.dontResetSize==false)j(r);if(r.firstLoad)i(r,'check');r.firstLoad=false;r.dontResetSize=false;},p=function(){var s=this;var q=s.originalElement;q.removeListener('load',o);q.removeListener('error',p);q.removeListener('abort',p);var r=CKEDITOR.getUrl('skins/'+m.config.skin+'/images/dialog.noimage.gif');if(s.preview)s.preview.setAttribute('src',r);CKEDITOR.document.getById('ImagePreviewLoader').setStyle('display','none');i(s,false);};return{title:n=='image'?m.lang.image.title:m.lang.image.titleButton,minWidth:450,minHeight:400,onShow:function(){var u=this;u.imageElement=false;u.linkElement=false;
u.imageEditMode=false;u.linkEditMode=false;u.lockRatio=true;u.dontResetSize=false;u.firstLoad=true;u.addLink=false;CKEDITOR.document.getById('ImagePreviewLoader').setStyle('display','none');u.preview=CKEDITOR.document.getById('previewImage');u.restoreSelection();var q=u.getParentEditor(),r=u.getParentEditor().getSelection().getSelectedElement();u.originalElement=q.document.createElement('img');u.originalElement.setCustomData('isReady','false');if(r&&r.getName()=='a'){u.linkElement=r;u.linkEditMode=true;var s=r.getChildren();if(s.count()==1){var t=s.getItem(0).getName();if(t=='img'||t=='input'){u.imageElement=s.getItem(0);if(u.imageElement.getName()=='img')u.imageEditMode='img';else if(u.imageElement.getName()=='input')u.imageEditMode='input';}}if(n=='image')u.setupContent(b,r);}else if(r&&r.getName()=='img'&&!r.getAttribute('_cke_protected_html'))u.imageEditMode='img';else if(r&&r.getName()=='input'&&r.getAttribute('type')&&r.getAttribute('type')=='image')u.imageEditMode='input';if(u.imageEditMode||u.imageElement){if(!u.imageElement)u.imageElement=r;u.setupContent(a,u.imageElement);i(u,true);}},onOk:function(){var s=this;if(s.imageEditMode){var q=s.imageEditMode,r=s.imageElement;if(n=='image'&&q=='input'&&confirm(m.lang.image.button2Img)){q='img';s.imageElement=m.document.createElement('img');r.insertBeforeMe(s.imageElement);r.remove(false);}else if(n!='image'&&q=='img'&&confirm(m.lang.image.img2Button)){q='input';s.imageElement=m.document.createElement('input');s.imageElement.setAttribute('type','image');r.insertBeforeMe(s.imageElement);r.remove(false);}}else if(n=='image')s.imageElement=m.document.createElement('img');else{s.imageElement=m.document.createElement('input');s.imageElement.setAttribute('type','image');}if(s.linkEditMode==false)s.linkElement=m.document.createElement('a');s.commitContent(a,s.imageElement);s.commitContent(b,s.linkElement);s.restoreSelection();s.clearSavedSelection();if(s.imageEditMode==false){s.restoreSelection();s.clearSavedSelection();if(s.addLink){if(s.linkEditMode==false){s.linkElement.append(s.imageElement,false);m.insertElement(s.linkElement);}else s.linkElement.append(s.imageElement,false);}else m.insertElement(s.imageElement);}else if(s.linkEditMode==false&&s.addLink){s.imageElement.insertBeforeMe(s.linkElement);s.imageElement.appendTo(s.linkElement);}else if(s.linkEditMode==true&&s.addLink==false)s.linkElement.remove(true);},onLoad:function(){if(n!='image')this.hidePage('Link');},onHide:function(){var q=this;if(q.preview)q.commitContent(d,q.preview);
if(q.originalElement){q.originalElement.removeListener('load',o);q.originalElement.removeListener('error',p);q.originalElement.removeListener('abort',p);q.originalElement.remove();q.originalElement=false;}},contents:[{id:'info',label:m.lang.image.infoTab,accessKey:'I',elements:[{type:'vbox',padding:0,children:[{type:'html',html:'<span>'+CKEDITOR.tools.htmlEncode(m.lang.image.url)+'</span>'},{type:'hbox',widths:['280px','110px'],align:'right',children:[{id:'txtUrl',type:'text',label:'',onChange:function(){var q=this.getDialog(),r=this.getValue();if(r.length>0){var q=this.getDialog(),s=q.originalElement;s.setCustomData('isReady','false');var t=CKEDITOR.document.getById('ImagePreviewLoader');if(t)t.setStyle('display','');s.on('load',o,q);s.on('error',p,q);s.on('abort',p,q);s.setAttribute('src',r);q.preview.setAttribute('src',r);h(q);}},setup:function(q,r){if(q==a){var s=this.getDialog(),t=r.getAttribute('_cke_saved_src');if(!t)t=r.getAttribute('src');s.dontResetSize=true;this.setValue(t);this.focus();}},commit:function(q,r){var s=this;if(q==a&&(s.getValue()!=''||s.isChanged())){r.setAttribute('_cke_saved_src',decodeURI(s.getValue()));r.setAttribute('src',decodeURI(s.getValue()));}else if(q==d){r.setAttribute('src','');r.removeAttribute('src');}}},{type:'button',id:'browse',align:'center',label:m.lang.common.browseServer,onLoad:function(){var q=this.getDialog();if(q.getParentEditor().config.image_browseServer==false)q.getContentElement('info','browse').getElement().hide();},onClick:function(){}}]}]},{id:'txtAlt',type:'text',label:m.lang.image.alt,accessKey:'A','default':'',onChange:function(){h(this.getDialog());},setup:function(q,r){if(q==a)this.setValue(r.getAttribute('alt'));},commit:function(q,r){var s=this;if(q==a)if(s.getValue()!=''||s.isChanged())r.setAttribute('alt',s.getValue());else if(q==c)r.setAttribute('alt',s.getValue());else if(q==d)r.removeAttribute('alt');}},{type:'hbox',widths:['140px','240px'],children:[{type:'vbox',padding:10,children:[{type:'hbox',widths:['70%','30%'],children:[{type:'vbox',padding:1,children:[{type:'text',id:'txtWidth',labelLayout:'horizontal',label:m.lang.image.width,onKeyUp:g,validate:function(){var q=this.getValue().match(f);if(!q)alert(m.lang.common.validateNumberFailed);return!!q;},setup:k,commit:function(q,r){var v=this;if(q==a){var s=v.getValue();if(s!='')r.setAttribute('width',s);else if(s==''&&v.isChanged())r.removeAttribute('width');}else if(q==c){var s=v.getValue(),t=s.match(e);if(!t){var u=v.getDialog().originalElement;
if(u.getCustomData('isReady')=='true')r.setStyle('width',u.$.width+'px');}else r.setStyle('width',s+'px');}else if(q==d){r.setStyle('width','0px');r.removeAttribute('width');r.removeStyle('width');}}},{type:'text',id:'txtHeight',labelLayout:'horizontal',label:m.lang.image.height,onKeyUp:g,validate:function(){var q=this.getValue().match(f);if(!q)alert(m.lang.common.validateNumberFailed);return!!q;},setup:k,commit:function(q,r){var v=this;if(q==a){var s=v.getValue();if(s!='')r.setAttribute('height',s);else if(s==''&&v.isChanged())r.removeAttribute('height');}else if(q==c){var s=v.getValue(),t=s.match(e);if(!t){var u=v.getDialog().originalElement;if(u.getCustomData('isReady')=='true')r.setStyle('height',u.$.height+'px');}else r.setStyle('height',s+'px');}else if(q==d){r.setStyle('height','0px');r.removeAttribute('height');r.removeStyle('height');}}}]},{type:'html',style:'position:relative;top:10px;height:50px;',onLoad:function(){var q=CKEDITOR.document.getById('btnResetSize');ratioButton=CKEDITOR.document.getById('btnLockSizes');if(q){q.on('click',function(){j(this);},this.getDialog());q.on('mouseover',function(){this.addClass('BtnOver');},q);q.on('mouseout',function(){this.removeClass('BtnOver');},q);}if(ratioButton){ratioButton.on('click',function(){var v=this;var r=i(v),s=v.originalElement,t=v.getValueOf('info','txtWidth');if(s.getCustomData('isReady')=='true'&&t){var u=s.$.height/s.$.width*t;if(!isNaN(u)){v.setValueOf('info','txtHeight',Math.round(u));h(v);}}},this.getDialog());ratioButton.on('mouseover',function(){this.addClass('BtnOver');},ratioButton);ratioButton.on('mouseout',function(){this.removeClass('BtnOver');},ratioButton);}},html:'<div><div title="'+m.lang.image.lockRatio+'" class="BtnLocked" id="btnLockSizes"></div>'+'<div title="'+m.lang.image.resetSize+'" class="BtnReset" id="btnResetSize"></div>'+'</div>'}]},{type:'vbox',padding:1,children:[{type:'text',id:'txtBorder',labelLayout:'horizontal',label:m.lang.image.border,'default':'',onKeyUp:function(){h(this.getDialog());},validate:function(){var q=CKEDITOR.dialog.validate.integer(m.lang.common.validateNumberFailed);return q.apply(this);},setup:function(q,r){if(q==a)this.setValue(r.getAttribute('border'));},commit:function(q,r){var t=this;if(q==a)if(t.getValue()!=''||t.isChanged())r.setAttribute('border',t.getValue());else if(q==c){var s=parseInt(t.getValue(),10);s=isNaN(s)?0:s;r.setAttribute('border',s);r.setStyle('border',s+'px solid black');}else if(q==d){r.removeAttribute('border');r.removeStyle('border');
}}},{type:'text',id:'txtHSpace',labelLayout:'horizontal',label:m.lang.image.hSpace,'default':'',onKeyUp:function(){h(this.getDialog());},validate:function(){var q=CKEDITOR.dialog.validate.integer(m.lang.common.validateNumberFailed);return q.apply(this);},setup:function(q,r){if(q==a){var s=r.getAttribute('hspace');if(s!=-1)this.setValue(s);}},commit:function(q,r){var t=this;if(q==a)if(t.getValue()!=''||t.isChanged())r.setAttribute('hspace',t.getValue());else if(q==c){var s=parseInt(t.getValue(),10);s=isNaN(s)?0:s;r.setAttribute('hspace',s);r.setStyle('margin-left',s+'px');r.setStyle('margin-right',s+'px');}else if(q==d){r.removeAttribute('hspace');r.removeStyle('margin-left');r.removeStyle('margin-right');}}},{type:'text',id:'txtVSpace',labelLayout:'horizontal',label:m.lang.image.vSpace,'default':'',onKeyUp:function(){h(this.getDialog());},validate:function(){var q=CKEDITOR.dialog.validate.integer(m.lang.common.validateNumberFailed);return q.apply(this);},setup:function(q,r){if(q==a)this.setValue(r.getAttribute('vspace'));},commit:function(q,r){var t=this;if(q==a)if(t.getValue()!=''||t.isChanged())r.setAttribute('vspace',t.getValue());else if(q==c){var s=parseInt(t.getValue(),10);s=isNaN(s)?0:s;r.setAttribute('vspace',t.getValue());r.setStyle('margin-top',s+'px');r.setStyle('margin-bottom',s+'px');}else if(q==d){r.removeAttribute('vspace');r.removeStyle('margin-top');r.removeStyle('margin-bottom');}}},{id:'cmbAlign',type:'select',labelLayout:'horizontal',widths:['35%','65%'],style:'width:100%',label:m.lang.image.align,'default':'',items:[[m.lang.common.notSet,''],[m.lang.image.alignLeft,'left'],[m.lang.image.alignAbsBottom,'absBottom'],[m.lang.image.alignAbsMiddle,'absMiddle'],[m.lang.image.alignBaseline,'baseline'],[m.lang.image.alignBottom,'bottom'],[m.lang.image.alignMiddle,'middle'],[m.lang.image.alignRight,'right'],[m.lang.image.alignTextTop,'textTop'],[m.lang.image.alignTop,'top']],onKeyUp:function(){h(this.getDialog());},setup:function(q,r){if(q==a)this.setValue(r.getAttribute('align'));},commit:function(q,r){var s=this;if(q==a)if(s.getValue()!=''||s.isChanged())r.setAttribute('align',s.getValue());else if(q==c)r.setAttribute('align',s.getValue());else if(q==d)r.removeAttribute('align');}}]}]},{type:'vbox',height:'250px',children:[{type:'html',style:'width:95%;',html:'<div>'+CKEDITOR.tools.htmlEncode(m.lang.image.preview)+'<br>'+'<div id="ImagePreviewLoader" style="display:none"><div class="loading">&nbsp;</div></div>'+'<div id="ImagePreviewBox">'+'<a href="javascript:void(0)" target="_blank" onclick="return false;" id="previewLink">'+'<img id="previewImage" src="" /></a>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. '+'Maecenas feugiat consequat diam. Maecenas metus. Vivamus diam purus, cursus a, commodo non, facilisis vitae, '+'nulla. Aenean dictum lacinia tortor. Nunc iaculis, nibh non iaculis aliquam, orci felis euismod neque, sed ornare massa mauris sed velit. Nulla pretium mi et risus. Fusce mi pede, tempor id, cursus ac, ullamcorper nec, enim. Sed tortor. Curabitur molestie. Duis velit augue, condimentum at, ultrices a, luctus ut, orci. Donec pellentesque egestas eros. Integer cursus, augue in cursus faucibus, eros pede bibendum sem, in tempus tellus justo quis ligula. Etiam eget tortor. Vestibulum rutrum, est ut placerat elementum, lectus nisl aliquam velit, tempor aliquam eros nunc nonummy metus. In eros metus, gravida a, gravida sed, lobortis id, turpis. Ut ultrices, ipsum at venenatis fringilla, sem nulla lacinia tellus, eget aliquet turpis mauris non enim. Nam turpis. Suspendisse lacinia. Curabitur ac tortor ut ipsum egestas elementum. Nunc imperdiet gravida mauris.'+'</div>'+'</div>'}]}]}]},{id:'Link',label:m.lang.link.title,padding:0,elements:[{id:'txtUrl',type:'text',label:m.lang.image.url,style:'width: 100%','default':'',setup:function(q,r){if(q==b){var s=r.getAttribute('_cke_saved_href');
if(!s)s=r.getAttribute('href');this.setValue(s);}},commit:function(q,r){var s=this;if(q==b){if(s.getValue()!=''||s.isChanged()){r.setAttribute('_cke_saved_href',decodeURI(s.getValue()));r.setAttribute('href','javascript:void(0)/*'+CKEDITOR.tools.getNextNumber()+'*/');if(s.getValue()!=''||m.config.image_removeLinkByEmptyURL==false)s.getDialog().addLink=true;}}}},{type:'button',id:'browse',style:'float:right',label:m.lang.common.browseServer,onClick:function(){}},{id:'cmbTarget',type:'select',label:m.lang.link.target,'default':'',items:[[m.lang.link.targetNotSet,''],[m.lang.link.targetNew,'_blank'],[m.lang.link.targetTop,'_top'],[m.lang.link.targetSelf,'_self'],[m.lang.link.targetParent,'_parent']],setup:function(q,r){if(q==b)this.setValue(r.getAttribute('target'));},commit:function(q,r){if(q==b){if(this.getValue()!=''||this.isChanged())r.setAttribute('target',this.getValue());}}}]},{id:'Upload',label:m.lang.image.upload,elements:[{type:'file',id:'upload',label:m.lang.image.btnUpload,action:m.config.image_uploadAction,size:38},{type:'fileButton',id:'uploadButton',label:m.lang.image.btnUpload,'for':['Upload','upload']}]},{id:'advanced',label:m.lang.common.advancedTab,elements:[{type:'hbox',widths:['50%','25%','25%'],children:[{type:'text',id:'linkId',label:m.lang.common.id,setup:function(q,r){if(q==a)this.setValue(r.getAttribute('id'));},commit:function(q,r){if(q==a){if(this.getValue()!=''||this.isChanged())r.setAttribute('id',this.getValue());}}},{id:'cmbLangDir',type:'select',style:'width : 100%;',label:m.lang.common.langDir,'default':'',items:[[m.lang.common.notSet,''],[m.lang.common.langDirLtr,'ltr'],[m.lang.common.langDirRtl,'rtl']],setup:function(q,r){if(q==a)this.setValue(r.getAttribute('dir'));},commit:function(q,r){if(q==a){if(this.getValue()!=''||this.isChanged())r.setAttribute('dir',this.getValue());}}},{type:'text',id:'txtLangCode',label:m.lang.common.langCode,'default':'',setup:function(q,r){if(q==a)this.setValue(r.getAttribute('lang'));},commit:function(q,r){if(q==a){if(this.getValue()!=''||this.isChanged())r.setAttribute('lang',this.getValue());}}}]},{type:'text',id:'txtGenLongDescr',label:m.lang.common.longDescr,setup:function(q,r){if(q==a)this.setValue(r.getAttribute('longDesc'));},commit:function(q,r){if(q==a){if(this.getValue()!=''||this.isChanged())r.setAttribute('longDesc',this.getValue());}}},{type:'hbox',widths:['50%','50%'],children:[{type:'text',id:'txtGenClass',label:m.lang.common.cssClass,'default':'',setup:function(q,r){if(q==a)this.setValue(r.getAttribute('class'));
},commit:function(q,r){if(q==a){if(this.getValue()!=''||this.isChanged())r.setAttribute('class',this.getValue());}}},{type:'text',id:'txtGenTitle',label:m.lang.common.advisoryTitle,'default':'',onChange:function(){h(this.getDialog());},setup:function(q,r){if(q==a)this.setValue(r.getAttribute('title'));},commit:function(q,r){var s=this;if(q==a)if(s.getValue()!=''||s.isChanged())r.setAttribute('title',s.getValue());else if(q==c)r.setAttribute('title',s.getValue());else if(q==d)r.removeAttribute('title');}}]},{type:'text',id:'txtdlgGenStyle',label:m.lang.common.cssStyle,'default':'',setup:function(q,r){if(q==a){var s=r.getAttribute('style');if(!s&&r.$.style.cssText)s=r.$.style.cssText;this.setValue(s);var t=r.$.style.height,u=r.$.style.width,v=(t?t:'').match(e),w=(u?u:'').match(e);this.attributesInStyle={height:!!v,width:!!w};}},commit:function(q,r){var t=this;if(q==a&&(t.getValue()!=''||t.isChanged())){r.setAttribute('style',t.getValue());var s=r.getAttribute('height');width=r.getAttribute('width');if(t.attributesInStyle&&t.attributesInStyle.height)if(s&&s!='')if(s.match(e)[2]=='%')r.setStyle('height',s+'%');else r.setStyle('height',s+'px');else r.removeStyle('height');if(t.attributesInStyle&&t.attributesInStyle.width)if(width&&width!=''){if(width.match(e)[2]=='%')r.setStyle('width',width+'%');else r.setStyle('width',width+'px');}else r.removeStyle('width');}}}]}]};};CKEDITOR.dialog.add('image',function(m){return l(m,'image');});CKEDITOR.dialog.add('imagebutton',function(m){return l(m,'imagebutton');});})();
