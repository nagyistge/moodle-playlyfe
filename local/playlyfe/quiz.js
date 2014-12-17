function setup(version, data) {
  var index = 0;
  var leaderboard = data.leaderboard;
  var rewards = data.rewards;
  var metrics = data.metrics;

  if(leaderboard !== null) {
    $('#leaderboard').append(selectLeaderboard(metrics, leaderboard));
    $('#leaderboard_enable').prop('checked', true);
  }
  for(;index<rewards.length; index++) {
    $('#reward tbody').append(addReward(metrics, index, rewards[index]));
    (function(index) {
      $('#close'+index).click(function() {
        $('#row'+index).remove();
      });
    })(index);
  }
  $('#leaderboard_enable').click(function() {
    if(this.checked === true) {
      $('#leaderboard').append(selectLeaderboard(metrics, leaderboard));
    }
    else {
      $('#leaderboard_metric').remove();
    }
  });
  $('#add').click(function() {
    $('#reward tbody').append(addReward(metrics, index));
    (function(i) {
      $('#close'+i).click(function() {
        $('#row'+i).remove();
      });
    })(index);
    index++;
  });
}
