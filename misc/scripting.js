'use strict';

var Zap = {
    pre_subscribe: function(bundle) {
        bundle.request.method = 'POST';
        bundle.request.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        bundle.request.data = $.param({
            url: bundle.subscription_url
        });
        return bundle.request;
    },
    post_subscribe: function(bundle) {
		// must return a json serializable object for use in pre_unsubscribe
		var data = JSON.parse(bundle.response.content);
		// we need this in order to build the {{webhook_id}}
		// in the rest hook unsubscribe url
		return {webhook_id: data.id};
	},
	pre_unsubscribe: function(bundle) {
		bundle.request.method = 'DELETE';
		// bundle.subscribe_data is from return date in post_subscribe method
		bundle.request.url = 'http://www.example.com/unsubscribe?id=' + bundle.subscribe_data.webhook_id;
		bundle.request.data = null;
		return bundle.request;
	}
    
};
