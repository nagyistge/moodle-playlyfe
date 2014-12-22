function selectMetric(id, metrics, index, reward) {
  var html = '<select name="metrics['+id+'][]">';
  for(var i=0; i<metrics.length; i++){
    var metric = metrics[i];
    if(metric.type === 'set') {
      html += '<optgroup label="'+metric.id+'">';
      for(var j=0;j<metric.constraints.items.length;j++) {
        var item = metric.constraints.items[j];
        if(reward !== undefined && metric.id === reward.metric.id && Object.keys(reward.value)[0] === item.name) {
          html += '<option selected="selected" value="'+metric.id+':'+item.name+'">'+item.name+'</option>';
        }
        else {
          html += '<option value="'+metric.id+':'+item.name+'">'+item.name+'</option>';
        }
      }
      html += '</optgroup>';
    }
    else {
      if(reward !== undefined && metric.id === reward.metric.id) {
        html += '<option selected="selected">'+ metric.id+'</option>';
      }
      else {
        html += '<option>'+metric.id+'</option>';
      }
    }
  }
  html += '</select>';
  return html;
}

function addReward(id, metrics, index, reward) {
  id = id || '';
  var html = '<tr id="row_'+id+index+'" class="r'+index+' centeralign">';
  html += '<td>'+selectMetric(id, metrics, index, reward)+'</td>';
  close_button = '<a style="float:right" id="close_'+id+index+'">remove</a>';
  if(reward !== undefined) {
    var value = reward.value;
    if(reward.metric.type === 'set') {
      value = reward.value[Object.keys(reward.value)[0]];
    }
    html += '<td><div id="col_'+index+'"><input name="values['+id+'][]" type="number" value="'+value+'" required />'+close_button+'</div></td>';
  }
  else {
    html += '<td><div id="col'+index+'"><input name="values['+id+'][]" type="number" value="1" required />'+close_button+'</div></td>';
  }
  html += '</tr>';
  return html;
}

function init_table(version, data) {
  var id = data.id;
  var metrics = data.metrics;
  var rewards = data.rewards;
  for(var index = 0;index<rewards.length; index++) {
    $('#treward_'+id+' tbody').append(addReward(id, metrics, index, rewards[index]));
    (function(i) {
      $('#close_'+id+i).click(function() {
        $('#row_'+id+i).remove();
      });
    })(index);
  }
}

function add_handler(version, data) {
  var id = data.id;
  var metrics = data.metrics;
  (function () {
    var index = 0;
    $('#add_'+id).click(function() {
      $('#treward_'+id+' tbody').append(addReward(id, metrics, index));
      (function(i) {
        $('#close_'+id+i).click(function() {
          $('#row_'+id+i).remove();
        });
      })(index);
      index++;
    });
  })();
}

function show_rewards(version, data) {
  console.log(data);
  $("#dialog_"+data).dialog({
    dialogClass: "no-close",
    closeOnEscape: false,
    //draggable: false,
    //resizable: false,
    height: "auto",
    width: "auto",
    modal: true,
    //position: { my: "center", at: "center", of: "body" }
    buttons: [
      {
        text: "OK",
        click: function() {
          $(this).dialog("close");
          if(data >= 0) {
            show_rewards('', --data);
          }
        }
      }
    ]
  });
}

function show_course_group(version, data) {
  add_course_group(version, data);
  $('#add').click(function() {
    add_course_group(version, data);
  });
}

var groups_count = 0;
function add_course_group(version, data) {
  groups_count++;
  var courses = data.courses;
  var metrics = data.metrics;
  html = '<div class="box generalbox authsui"><h1>'+groups_count+'</h1>';
  for(var i = 0; i < courses.length; i++) {
    var course = courses[i];
    html += '<input type="checkbox" value="'+course.id+'" name="courses['+groups_count+'][]" />'+course.name+'<br>';
  }
  html += '<table id="treward_'+groups_count+'" class="generaltable">';
  html += '<thead>';
  html += '<tr>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Metric</th>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
  html += '</tr>';
  html += '</thead>';
  html += '<tbody>';
  html += '</tbody>';
  html += '</table>';
  html += '<p><button type="button" id="add_'+groups_count+'">Add Reward</button></p></div>';
  $('#course_group').append(html);
  add_handler('', { id: groups_count, metrics: metrics });
}
