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
  (function(){
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

function render_reward(event) {
  metric= event.metric;
  delta = event.delta;
  html = '<img style="float: left;"" src="/local/playlyfe/image_def.php?metric='+metric.id+'&size=medium"></img>';
  if (metric.type === 'point') {
    value = delta['new'] - delta.old;
  }
  else {
    for(var key in delta) {
      if(delta.old !== null) {
        value = (delta[key]['new'] - delta[key].old)+' x '+key;
      }
      else {
         value = (delta[key]['new'])+' x '+key;
      }
      value += '     <img src="/local/playlyfe/image_def.php?metric='+metric.id+'&size=medium&item='+key+'"></img>    ';
    }
  }
  html += '<p><br>You have gained <b>'+value+'</b> '+metric.name + '</p>';
  html += '<div style="clear: both;"></div>';
  return html;
}

function show_dailog(event, leaderboard, data) {
  var btnText = "Next";
  if(data.events.length === 0) {
    btnText = "OK";
  }
  if(dialog_open === false) {
    $("#dialog").dialog({
      dialogClass: "no-close",
      closeOnEscape: false,
      //draggable: false,
      //resizable: false,
      position: { my: "center", at: "center", of: "body" },
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
              dialog_open = false;
            }
            else {
              show_rewards('', data);
            }
          }
        }
      ]
    });
    dialog_open = true;
  }
  var html = '';
  for(var i=0; i<event.changes.length; i++) {
    html += render_reward(event.changes[i]);
  }
  html += leaderboard;
  $("#ui-id-1").html(event.rule.name);
  $("#dialog").html('<h3>You have Gained</h3>'+html);
  $('.ui-dialog').css("top","20%");
}

var dialog_open = false;
function show_rewards(version, data) {
  var event = data.events.pop();
  var leaderboard = data.leaderboards.pop();
  show_dailog(event, leaderboard, data);
}

var groups_count = 0;
function add_course_group(version, data) {
  groups_count++;
  var courses = data.courses;
  var metrics = data.metrics;
  var rewards = data.rewards;
  var html = '';
  html += '<div id="cg_'+groups_count+'">';
  html += '<h3> Course Group '+groups_count+'</h3><hr></hr>';
  html += '<p> Please select the courses and add rewards to give when all of them have been completed </p>';
  html += '<table class="pl-table">';
  html += '<thead>';
  html += '<tr>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Course</th>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Select</th>';
  html += '</tr>';
  html += '</thead>';
  html += '<tbody>';
  for(var i = 0; i < courses.length; i++) {
    var course = courses[i];
    html += '<tr>';
    html += '<td>'+course.name+'</td>';
    html += '<td class="pl-leaderboard-checkbox">';
    if(course.selected) {
      html += '<input type="checkbox" value="'+course.id+'" name="courses['+groups_count+'][]" checked/>';
    }
    else {
      html += '<input type="checkbox" value="'+course.id+'" name="courses['+groups_count+'][]" />';
    }
    html += '</td>';
    html += '</tr>';
  }
  var id = 'course_group_'+groups_count+'_completed';
  html += '</tbody>';
  html += '</table>';
  html += '<table id="treward_'+id+'" class="pl-table">';
  html += '<thead>';
  html += '<tr>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Metric</th>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
  html += '</tr>';
  html += '</thead>';
  html += '<tbody>';
  html += '</tbody>';
  html += '</table>';
  html += '<p><button type="button" id="add_'+id+'">Add Reward</button></p>';
  html += '<p><button type="button" id="remove_'+groups_count+'">Remove Group</button></p>';
  html += '</div>';
  $('#course_group').append(html);
  (function(i) {
    $('#remove_'+i).click(function() {
      $('#cg_'+i).remove();
      groups_count--;
    });
  })(groups_count);
  if(rewards !== null && typeof rewards !== 'undefined') {
    init_table('', { id: id, metrics: metrics, rewards: rewards });
  }
  add_handler('', { id: id, metrics: metrics });
}

function handle_course_group_add(version, data) {
  $('#add').click(function() {
    add_course_group(version, data);
  });
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

function add_item(version, item) {
  item = item || {};
  item.name = item.name || '';
  item.description = item.description || '';
  item.max = item.max || '1';
  $('#extra').append(
    '<div id="item_'+index+'" class="generalbox authsui">'
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

function create_select(name, options, selected) {
  selected = selected || '';
  var html = '<select name="'+name+'" id="'+name+'">';
  for(var key in options) {
    if(selected === key) {
      html += '<option value="'+options[key]+'" selected>'+key+'</option>';
    }
    else {
      html += '<option value="'+options[key]+'">'+key+'</option>';
    }
  }
  html += '</select>';
  return html;
}

function create_condition_operator(id, condition) {
  var html = '';
  html += '<select name="condition_operators['+id+'][]">';
   var exists = false;
  if(condition !== null && typeof condition !== 'undefined') {
    exists = true;
  }
  if(exists && condition.operator === 'gt') {
    html += '<option value="gt" selected>></option>';
  }
  else {
    html += '<option value="gt">></option>';
  }
  if(exists && condition.operator === 'ge') {
    html += '<option value="ge" selected>>=</option>';
  }
  else {
    html += '<option value="ge">>=</option>';
  }
  if(exists && condition.operator === 'lt') {
    html += '<option value="lt" selected><</option>';
  }
  else {
    html += '<option value="lt"><</option>';
  }
  if(exists && condition.operator === 'le') {
    html += '<option value="le" selected><</option>';
  }
  else {
    html += '<option value="le"><=</option>';
  }
  if(exists && condition.operator === 'eq') {
    html += '<option value="eq" selected>=</option>';
  }
  else {
    html += '<option value="eq">=</option>';
  }
  if(exists && condition.operator === 'neq') {
    html += '<option value="neq" selected>≠</option>';
  }
  else {
    html += '<option value="neq">≠</option>';
  }
  html += '</select>';
  return html;
}

function create_condition(id, index, context) {
  //{ score: 'score', timecompleted: 'timecompleted', timeenrolled: 'timeenrolled' }
  var html = '<tr id="row_condition_'+id+index+'" class="r'+index+' centeralign">';
  html += '<td>'+create_select('condition_types['+id+'][]', { score: 'score' }, '')+'</td>';
  html += '<td>'+create_condition_operator(id, context)+'</td>';
  close_button = '<a class="remove-button" id="close_condition_'+id+index+'">remove</a>';
  var value = context.rhs || '1';
  html += '<td><div id="col'+index+'"><input name="condition_values['+id+'][]" type="number" value="'+value+'" required />'+close_button+'</div></td>';
  html += '</tr>';
  return html;
}

function create_rule_table(version, data) {
  var rules_count = 0;
  var id = data.rule.id;
  for(var i=0;i<data.rule.rules.length;i++) {
    var rule_id = id+'_'+rules_count;
    createRule(rules_count, rule_id , data.metrics, data.rule.rules[i].rewards, data.rule.rules[i].requires);
    (function(rule_id) {
      $('#remove_rule_'+rule_id).click(function() {
        $('#rule_'+rule_id).remove();
        //rules_count--;
      });
    })(rule_id);
    rules_count++;
  }
  $('#add_rule').click(function() {
    var rule_id = id+'_'+rules_count;
    createRule(rules_count, rule_id, data.metrics, [], {});
    rules_count++;
    $('#remove_rule_'+rule_id).click(function() {
      $('#rule_'+rule_id).remove();
      //rules_count--;
    });
  });
}

function createRule(rule_index, id, metrics, rewards, requires) {
  var html = '';
  html += '<div id="rule_'+id+'">';
  html += '<h3 class="underline">Rule '+(rule_index+1)+'</h3>';
  html += '<table id="treward_'+id+'" class="generaltable" style="float: left;">';
  html += '<thead>';
  html += '<tr>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Metric</th>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
  html += '</tr>';
  html += '</thead>';
  html += '<tbody>';
  html += '</tbody>';
  html += '</table>';
  //html += 'With Conditions';
  html += '<table id="tcondition_'+id+'" class="generaltable">';
  html += '<thead>';
  html += '<tr>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Type</th>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Operator</th>';
  html += '<th class="header c1 lastcol centeralign" style="" scope="col">Value</th>';
  html += '</tr>';
  html += '</thead>';
  html += '<tbody>';
  html += '</tbody>';
  html += '</table>';
  html += '<div style="clear: both;"></div>';
  html += '<button type="button" id="add_'+id+'">Add Reward</button>';
  html += '<button type="button" id="add_condition_'+id+'">Add Condition</button>';
  html += '<button type="button" id="remove_rule_'+id+'">Remove Rule</button>';
  html += '</div>';
  $("#rule_table").append(html);
  for(var j=0;j<rewards.length;j++) {
    $('#treward_'+id+' tbody').append(addReward(id, metrics, j, rewards[j]));
    (function(j) {
      $('#close_'+id+j).click(function() {
        $('#row_'+id+j).remove();
      });
    })(j);
  }
  (function () {
    var index = j;
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
  var condition_index = 0;
  if(requires.type === 'var') {
    $('#tcondition_'+id+' tbody').append(create_condition(id, condition_index, requires.context));
    (function(i) {
      $('#close_condition_'+id+i).click(function() {
        $('#row_condition_'+id+i).remove();
      });
    })(condition_index);
    condition_index++;
  }
  else if(requires.type === 'and') {
    for(var i=0;i<requires.expression.length;i++) {
      $('#tcondition_'+id+' tbody').append(create_condition(id, condition_index, requires.expression[i].context));
      (function(i) {
        $('#close_condition_'+id+i).click(function() {
          $('#row_condition_'+id+i).remove();
        });
      })(condition_index);
      condition_index++;
    }
  }
  (function () {
    $('#add_condition_'+id).click(function() {
      $('#tcondition_'+id+' tbody').append(create_condition(id, condition_index, {}));
      (function(i) {
        $('#close_condition_'+id+i).click(function() {
          $('#row_condition_'+id+i).remove();
        });
      })(condition_index);
      condition_index++;
    });
  })();
}
