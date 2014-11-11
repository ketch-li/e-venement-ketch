// the global var that can be used everywhere as a "root"
if ( LI == undefined )
  var LI = {};

LI.pad_num = function (num, digits){return(1e15+num+"").slice(-digits)}

// THE CURRENCY
LI.format_currency = function(value, nbsp, nodot)
{
  if ( nbsp  == undefined ) nbsp  = true;
  if ( nodot == undefined ) nodot = true;
  if ( !value ) value = 0;

  var r = $('.currency:first').length > 0
    ? $('.currency:first').html()
    : '%d €';
  value = r.replace('%d',value.toFixed(2));

  if ( nbsp  ) value = value.replace(' ','&nbsp;');
  if ( nodot ) value = value.replace('.',',');

  return value;
}

// display a flash for a limited time
LI.alert = function(msg, type, time)
{
  if ( time == undefined )
    time = 4000;
  if ( type == undefined )
    type = 'notice';
  
  var icons = {
    success: 'ui-icon-circle-check',
    notice:  'ui-icon-info',
    error:   'ui-icon-alert',
  }
  
  var flash = $('<div class="%%type%% ui-state-%%type%% ui-state-highlight ui-corner-all"><span class="ui-icon %%icon%% floatleft"></span>&nbsp;%%msg%%</div>'
    .replace('%%msg%%',msg)
    .replace('%%type%%',type).replace('%%type%%',type)
    .replace('%%icon%%',icons[type]))
    .hide()
    .appendTo($('.sf_admin_flashes').css('position', 'absolute').css('z-index', 10))
    .fadeIn('slow');
  setTimeout(function(){
    flash.fadeOut(function(){ $(this).remove(); })
  },time);
}

LI.isMobile = {
  Android: function() {
    return navigator.userAgent.match(/Android/i);
  },
  BlackBerry: function() {
    return navigator.userAgent.match(/BlackBerry/i);
  },
  iOS: function() {
    return navigator.userAgent.match(/iPhone|iPad|iPod/i);
  },
  Opera: function() {
    return navigator.userAgent.match(/Opera Mini/i);
  },
  Windows: function() {
    return navigator.userAgent.match(/IEMobile/i);
  },
  any: function() {
    return (LI.isMobile.Android() || LI.isMobile.BlackBerry() || LI.isMobile.iOS() || LI.isMobile.Opera() || LI.isMobile.Windows());
  },
  test: function() {
    return true;
  }
};
