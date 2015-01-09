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
  close_button = '<a style="float:right; padding-left: 25px;" id="close_'+id+index+'">remove</a>';
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
  var event = data.events.pop();
  var leaderboard = data.leaderboards.pop();
  show_dailog(event, leaderboard, data);
}

function show_dailog(event, leaderboard, data) {
  var btnText = "Next";
  if(data.events.length === 0) {
    btnText = "OK";
  }
  $("#dialog").dialog({
    dialogClass: "no-close",
    closeOnEscape: false,
    //draggable: false,
    //resizable: false,
    //position: { my: "center", at: "center", of: "body" }
    height: "auto",
    width: "auto",
    modal: true,
    title: event.rule.name,
    buttons: [
      {
        text: btnText,
        click: function() {
          if(data.events.length === 0) {
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
    html += render_reward(event.changes[i]);
  }
  html += leaderboard;
  $("#dialog").html('<h3>You have Gained</h3>'+html);
}

function render_reward(event) {
  metric= event.metric;
  delta = event.delta;
  html = '<img src="/local/playlyfe/image_def.php?metric='+metric.id+'&size=large"></img>';
  if (metric.type === 'point') {
    value = delta['new'] - delta.old;
  }
  else {
    for(key in delta) {
      if(delta['old'] !== null) {
        value = (delta[key]['new'] - delta[key]['old'])+' x '+key;
      }
      else {
         value = (delta[key]['new'])+' x '+key;
      }
      value += '     <img src="/local/playlyfe/image_def.php?metric='+metric.id+'&size=medium&item='+key+'"></img>    ';
    }
  }
  html += 'You have gained '+value+' '+metric.name;
  html += '<br>';
  return html;
}

function handle_course_group_add(version, data) {
  $('#add').click(function() {
    add_course_group(version, data);
  });
}

var groups_count = 0;
function add_course_group(version, data) {
  groups_count++;
  var courses = data.courses;
  var metrics = data.metrics;
  var rewards = data.rewards;
  html = '<div class="box generalbox authsui"><h1>'+groups_count+'</h1>';
  for(var i = 0; i < courses.length; i++) {
    var course = courses[i];
    if(course.selected) {
      html += '<input type="checkbox" value="'+course.id+'" name="courses['+groups_count+'][]" checked/>'+course.name+'<br>';
    }
    else {
      html += '<input type="checkbox" value="'+course.id+'" name="courses['+groups_count+'][]" />'+course.name+'<br>';
    }
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
  if(rewards !== null && typeof rewards !== 'undefined') {
    init_table('', { id: groups_count, metrics: metrics, rewards: rewards })
  }
  add_handler('', { id: groups_count, metrics: metrics });
}

var index = 0;

function init_set(version, new_index) {
  $('#add').click(function() {
    add_item();
  });
}

function create_input(title, name, value, type) {
  type = type || 'text';
  var html = '';
  html += '<div class="fitem required fitem_ftext">';
  html += '<div class="fitemtitle"><label>'+title+'<img class="req" title="Required field" alt="Required field" src="http://127.0.0.1:3000/theme/image.php/standard/core/1420616075/req"></label></div>';
  html += '<div class="felement ftext"><input name="'+name+'" type="'+type+'" value="'+value+'" required></div></div>';
  return html;
}

function create_file(title, name) {
  var html = '';
  html += '<div class="fitem required fitem_ftext">';
  html += '<div class="fitemtitle"><label>'+title+'</label></div>';
  html += '<div class="felement ftext"><input name="'+name+'" type="file"></div></div>';
  return html;
}

function create_checkbox(title, name) {
  var html = '';
  html += '<div class="fitem required fitem_ftext">';
  html += '<div class="fitemtitle"><label>'+title+'<img class="req" title="Required field" alt="Required field" src="http://127.0.0.1:3000/theme/image.php/standard/core/1420616075/req"></label></div>';
  html += '<div class="felement ftext"><input name="'+name+'" type="checkbox" checked></div></div>';
  return html;
}

function create_(title, name) {
  var html = '';
  html += '<div class="fitem required fitem_ftext">';
  html += '<div class="fitemtitle"><label>'+title+'<img class="req" title="Required field" alt="Required field" src="http://127.0.0.1:3000/theme/image.php/standard/core/1420616075/req"></label></div>';
  html += '<div class="felement ftext"><input name="'+name+'" type="checkbox" checked></div></div>';
  return html;
}

function add_item(version, item) {
  item = item || {};
  item.name = item.name || '';
  item.description = item.description || '';
  item.max = item.max || '1';
  $('#extra').append(
    '<div id="item_'+index+'" class="generalbox authsui" style="display: inline-block;">'
    + create_input('Name', 'items_names['+index+']', item.name)
    + create_input('Description', 'items_desc['+index+']', item.description)
    + create_input('Max', 'items_max['+index+']', item.max, 'number')
    + create_file('Image', 'itemfile'+index)
    + create_checkbox('Hidden', 'items_hidden['+index+']')
    +'<button type="button" id="remove_'+index+'">remove</button>'
    +'</div>'
  );
  (function(i) {
    $('#remove_'+i).click(function() {
      $('#item_'+i).remove();
      index--;
    });
  })(index);
  index++;
}

function init_conditions() {
  $('#condition_type').change(function(event){
    var value = $(this).find("option:selected").val();
    //console.log('hello', value);
    // if(value === 'none') {
    //   $('#condition_type').hide();
    // }
    // else {
    //   $('#condition_type').show();
    // }
  });
}
