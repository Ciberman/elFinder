
$.fn.elfinderdialog = function(opts) {
	var dialog;
	
	if (typeof(opts) == 'string' && (dialog = this.closest('.ui-dialog')).length) {
		if (opts == 'open' && dialog.is(':hidden')) {
			dialog.fadeIn(150, function() {
				dialog.trigger('open');
			});
		} else if (opts == 'close' && dialog.is(':visible')) {
			dialog.fadeOut(150, function() {
				dialog.trigger('close');
			});
		} else if (opts == 'destroy') {
			dialog.fadeOut(200, function() {
				dialog.remove();
			});
		}
	}
	
	opts = $.extend({}, $.fn.elfinderdialog.defaults, opts);

	this.filter(':not(.ui-dialog-content)').each(function() {
		var self       = $(this).addClass('ui-dialog-content ui-widget-content'),
			parent     = self.parent(),
			id         = parseInt(Math.random()*1000000),
			overlay    = parent.children('.elfinder-overlay'),
			buttonset  = $('<div class="ui-dialog-buttonset"/>'),
			buttonpane = $('<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix"/>')
				.append(buttonset),
			
			dialog = $('<div class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable std42-dialog  elfinder-dialog '+opts.cssClass+'"/>')
				.hide()
				.append(self)
				.appendTo(parent)
				.draggable({ handle : '.ui-dialog-titlebar'})
				.css({
					width  : opts.width,
					height : opts.height
				})
				.mousedown(function(e) {
					e.stopPropagation()
					$(document).mousedown();
					
					if (!dialog.is('.elfinder-dialog-active')) {
						parent.find('.elfinder-dialog:visible').removeClass('elfinder-dialog-active');
						dialog.addClass('elfinder-dialog-active').zIndex(maxZIndex() + 1);
					}
				})
				.bind('open', function() {
					opts.modal && overlay.elfinderoverlay('show');
					dialog.mousedown().find('.ui-button:first').focus();
					dialog.find(':text:first').focus();
					typeof(opts.open) == 'function' && $.proxy(opts.open, self[0])();

					if (!dialog.is('.elfinder-dialog-notify')) {
						
						parent.find('.elfinder-dialog:visible').not('.elfinder-dialog-notify').each(function() {
							var d     = $(this),
								top   = parseInt(d.css('top')),
								left  = parseInt(d.css('left')),
								_top  = parseInt(dialog.css('top')),
								_left = parseInt(dialog.css('left'))
								;

							if (d[0] != dialog[0] && (top >= _top || left >= _left)) {
								dialog.css({
									top  : (top+10)+'px',
									left : (left+10)+'px'
								});
							}
						});
					} 
				})
				.bind('close', function() {
					var dialogs = parent.find('.elfinder-dialog:visible'),
						z = maxZIndex();
					
					opts.modal && overlay.elfinderoverlay('hide');
					
					// get focus to next dialog
					if (dialogs.length) {
						dialogs.each(function() {
							var d = $(this);
							if (d.zIndex() >= z) {
								d.mousedown();
								return false;
							}
						})
					} else {
						// return focus to parent
						parent.mousedown();
					}
					
					typeof(opts.close) == 'function' && $.proxy(opts.close, self[0])();
				}),
				maxZIndex = function() {
					var z = parent.zIndex() + 10;
					parent.find('.elfinder-dialog:visible').each(function() {
						var _z;
						if (this != dialog[0]) {
							_z = $(this).zIndex();
							if (_z > z) {
								z = _z;
							}
						}
					})
					return z;
				},
				top
			;
		
		if (!opts.position) {
			top = parseInt((parent.height() - dialog.outerHeight())/2 - 42);
			opts.position = {
				top  : (top > 0 ? top : 0)+'px',
				left : parseInt((parent.width() - dialog.outerWidth())/2)+'px'
			}
		} 
			
		dialog.css(opts.position);

		if (opts.closeOnEscape) {
			$(document).bind('keyup.'+id, function(e) {
				if (e.keyCode == $.ui.keyCode.ESCAPE && dialog.is('.elfinder-dialog-active')) {
					self.elfinderdialog('close');
					$(document).unbind('keyup.'+id);
				}
			})
		}
		dialog.prepend(
			$('<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">'+opts.title+'</div>')
				.prepend($('<a href="#" class="ui-dialog-titlebar-close ui-corner-all"><span class="ui-icon ui-icon-closethick"/></a>')
					.click(function(e) {
						e.preventDefault();
						self.elfinderdialog('close');
					}))

		);
			
		
			
		$.each(opts.buttons, function(name, cb) {
			var button = $('<button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"><span class="ui-button-text">'+name+'</span></button>')
				.click($.proxy(cb, self[0]))
				.hover(function(e) { $(this)[e.type == 'mouseenter' ? 'focus' : 'blur']() })
				.focus(function() { $(this).addClass('ui-state-hover') })
				.blur(function() { $(this).removeClass('ui-state-hover') })
				.keydown(function(e) { 
					var next;
					
					if (e.keyCode == $.ui.keyCode.ENTER) {
						$(this).click();
					}  else if (e.keyCode == $.ui.keyCode.TAB) {
						next = $(this).next('.ui-button');
						next.length ? next.focus() : $(this).parent().children('.ui-button:first').focus()
					}
				})
			buttonset.append(button);
		})
			
		buttonset.children().length && dialog.append(buttonpane);
			
		if (opts.resizable && $.fn.resizable) {
			dialog.resizable({
					minWidth   : opts.minWidth,
					minHeight  : opts.minHeight,
					alsoResize : this
				});
			
		} 
			
		typeof(opts.create) == 'function' && $.proxy(opts.create, this)();
			
		opts.autoOpen && self.elfinderdialog('open');

	});
	
	return this;
}

$.fn.elfinderdialog.defaults = {
	cssClass  : '',
	title     : '',
	modal     : false,
	resizable : true,
	autoOpen  : true,
	closeOnEscape : true,
	buttons   : {},
	position  : null,
	width     : 300,
	height    : 'auto',
	minWidth  : 200,
	minHeight : 110
}