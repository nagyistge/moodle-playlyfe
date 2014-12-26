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
  var event = data.pop()
  show_dailog(event, data);
    //position: { my: "center", at: "center", of: "body" }
  //   buttons: [
  //     {
  //       text: "OK",
  //       click: function() {
  //         // $("#dialog").dialog({
  //         //   dialogClass: "no-close",
  //         //   closeOnEscape: false,
  //         //   title: 'gello'
  //         // });
  //         // $("#dialog").html('<h3>You have Gained</h3>'+render_reward(item));
  //       }
  //     }
  //   ]
  // });
}

function show_dailog(event, data) {
  $("#dialog").dialog({
    dialogClass: "no-close",
    closeOnEscape: false,
    //draggable: false,
    //resizable: false,
    height: "auto",
    width: "auto",
    modal: true,
    title: event.rule.name,
    buttons: [
      {
        text: "Next",
        click: function() {
          if(data.length === 0) {
            $( this ).dialog("close");
          }
          else {
            show_rewards('', data);
          }
        }
      }
    ]
  });
  var html = '';
  for(var i=0; i<event.changes.length; i++) {
    html = render_reward(event.changes[i]);
  }
  $("#dialog").html('<h3>You have Gained</h3>'+html);
}

function render_reward(event) {
  console.log(event);
  metric= event.metric;
  delta = event.delta;
  html = '<img src="image_def.php?metric='+metric.id+'&size=large"></img>';
  if (metric.type === 'point') {
    value = delta['new'] - delta.old;
  }
  // else {
  //   for(key in delta) {
  //     value = delta['new'] - $value['old']).' x '.$key;
  //     value += '     <img src="image_def.php?metric='.$metric['id'].'&size=medium&item='.$key.'"></img>    ';
  //   }
  // }
  html += 'You have gained '+value+' '+metric.name;
  return html;
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


var index = 0;

function init_set(version, new_index) {
  $('#add').click(function() {
    add_item();
  });
  if(new_index !== null && typeof new_index !== 'undefined') {
    index = new_index;
  }
}

function add_item() {
  $('#extra').append(
    '<div id="item_'+index+'" class="generalbox authsui">'
    +'Badge '+(index+1)+'<button type="button" id="remove_'+index+'">delete</button>'
    +'<p>Name: <input name="items_names['+index+']" type="text" required /></p>'
    +'<p>Description: <input name="items_desc['+index+']" type="text" required /></p>'
    +'<p>Max: <input name="items_max['+index+']" type="number" value="1" required /></p>'
    +'<input type="hidden" name="MAX_FILE_SIZE" value="500000000" />'
    +'<p>Badge Image: <input type="file" name="itemfile'+index+'" /></p>'
    +'<p>Hidden: <input name="items_hidden['+index+']" type="checkbox" checked /></p></div>'
  );
  (function(i) {
    $('#remove_'+i).click(function() {
      $('#item_'+i).remove();
      index--;
    });
  })(index);
  index++;
}
