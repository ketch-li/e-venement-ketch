if ( LI === undefined )
  var LI = {};
if ( LI.stats === undefined )
  LI.stats = [];

$(document).ready(function(){

  LI.stats.groups();
});

LI.stats.groups = function(){

	$('#content .jqplot').each(function(){

    var chart = $(this).find('.chart')
    var name = chart.attr('data-series-name');
    var id = chart.prop('id');
    var title = $(this).find('h2').prop('title') ? $(this).find('h2').prop('title')+': ' : '';
    LI.csvData[name] = [
      [
        title,
        $(this).find('h2').text()
      ],
    ]; 
    
    //retrieve stats
    $.get(chart.attr('data-json-url') + '?id=' + name, function(json){
      var array = [];
      var series = [];
      console.log(json);
      $.each(JSON.parse(json), function(i, data) {
        var nb = data.nb === null ? 0 : data.nb;
        array.push([data.date, nb]);
        LI.csvData[name].push([data.date, nb]);
      });
      $(this).dblclick(function(){
        $(this).resetZoom();
      });
      
      //init jqplot with data array
      $.jqplot(id, [array], {
          seriesDefaults: {
            showMarker: false
          },
          series: [{ label: title }],
          axes: {
            xaxis: {
              renderer: $.jqplot.DateAxisRenderer,
              tickOptions: { formatString:'%d/%m/%Y' }
            },
           yaxis: {
              min: name == 'web-origin' ? 0 : null,
              //tickInterval: 1,
              tickOptions: {
                formatString: '%d'
              }
            }
          },
          highlighter: {
            sizeAdjust: 2,
            show: true
          },
          legend: {
            show: false,
            location: 'e',
            placement: 'outside'
          },
          cursor: {
            show: true,
            showTooltip: false,
            zoom: true
          },
          captureRightClick: true
        });
    });
  });
};