function selectMetric(metrics, index, reward) {
  var html = '<select id="metrics_'+index+'" name="metrics[]">';
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

function addReward(metrics, index, reward) {
  var html = '<tr id="row'+index+'" class="r'+index+' centeralign">';
  html += '<td>'+selectMetric(metrics, index, reward)+'</td>';
  close_button = '<a style="float:right" id="close'+index+'">remove</a>';
  if(reward !== undefined) {
    var value = reward.value;
    if(reward.metric.type === 'set') {
      value = reward.value[Object.keys(reward.value)[0]];
    }
    html += '<td><div id="col'+index+'"><input name="values[]" type="number" value="'+value+'" required />'+close_button+'</div></td>';
  }
  else {
    html += '<td><div id="col'+index+'"><input name="values[]" type="number" value="1" required />'+close_button+'</div></td>';
  }
  html += '</tr>';
  return html;
}

function selectLeaderboard(metrics, leaderboard) {
  var html = '<div id="leaderboard_metric"><b>Leaderboard for the Metric:</b><select name="leaderboard_metric">';
  for(var i=0; i<metrics.length; i++){
    if(metrics[i].type === 'point') {
      if(leaderboard !== null && metrics[i].id === leaderboard) {
        html += '<option selected="selected">'+metrics[i].id+'</option>';
      }
      else {
        html += '<option>'+metrics[i].id+'</option>';
      }
    }
  }
  html += '</select></div>';
  return html;
}
