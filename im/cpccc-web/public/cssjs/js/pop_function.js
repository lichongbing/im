Vue.prototype.$formatTime = function (timestamp) {
    if (timestamp == 0) return '';
    var d_seconds,
        d_minutes,
        d_hours,
        d_days,
        timeNow = parseInt(new Date().getTime()/1000),
        d,

        date = new Date(timestamp*1000),
        Y = date.getFullYear()-2000,
        M = date.getMonth() + 1,
        D = date.getDate(),
        H = date.getHours(),
        m = date.getMinutes(),
        s = date.getSeconds();
    //小于10的在前面补0
    if (M < 10) {
        M = '0' + M;
    }
    if (D < 10) {
        D = '0' + D;
    }
    if (H < 10) {
        H = '0' + H;
    }
    if (m < 10) {
        m = '0' + m;
    }
    if (s < 10) {
        s = '0' + s;
    }

    d = timeNow - timestamp;
    d_days = parseInt(d/86400);
    d_hours = parseInt(d/3600);
    d_minutes = parseInt(d/60);
    d_seconds = parseInt(d);

    if(d_days > 0 && d_days < 7){
        return d_days + '天前';
    }else if(d_days <= 0 && d_hours > 0){
        return d_hours + '小时前';
    }else if(d_hours <= 0 && d_minutes > 0){
        return d_minutes + '分钟前';
    }else if (d_seconds < 60) {
        if (d_seconds <= 0) {
            return '刚刚';
        }else {
            //return d_seconds + '秒前';
            return '刚刚';
        }
    }else if (d_days >= 3 && d_days < 30){
        return Y + '/' + M + '/' + D;
    }else if (d_days >= 30) {
        return Y + '/' + M + '/' + D;
    }
};

Vue.prototype.$date = function(timestamp){
    var digit = function(num){
        return num < 10 ? '0' + (num|0) : num;
    };
    var d = new Date(timestamp * 1000||new Date());
    return digit(d.getMonth() + 1) + '-' + digit(d.getDate())
        + ' ' + digit(d.getHours()) + ':' + digit(d.getMinutes());
};

Vue.prototype.$isback = function(){
    return cpcccim.isBack;
};

if (!Object.values) Object.values = function(obj) {
    if (obj !== Object(obj))
        throw new TypeError('Object.values called on a non-object');
    var val=[],key;
    for (key in obj) {
        if (Object.prototype.hasOwnProperty.call(obj,key)) {
            val.push(obj[key]);
        }
    }
    return val;
};

if (typeof Object.assign != 'function') {
    // Must be writable: true, enumerable: false, configurable: true
    Object.defineProperty(Object, "assign", {
        value: function assign(target, varArgs) { // .length of function is 2
            'use strict';
            if (target == null) { // TypeError if undefined or null
                throw new TypeError('Cannot convert undefined or null to object');
            }

            let to = Object(target);

            for (var index = 1; index < arguments.length; index++) {
                var nextSource = arguments[index];

                if (nextSource != null) { // Skip over if undefined or null
                    for (let nextKey in nextSource) {
                        // Avoid bugs when hasOwnProperty is shadowed
                        if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                            to[nextKey] = nextSource[nextKey];
                        }
                    }
                }
            }
            return to;
        },
        writable: true,
        configurable: true
    });
}


