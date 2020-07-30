/**
 * cpcccim.com
 */

function is_dangerous_url(url) {
    url = url.toLowerCase();
    // url不允许有javascript关键字
    if (url.indexOf('javascript') != -1) return true;
    // url里必须以/或http或https开头
    if (url.indexOf('/') != 0 && url.indexOf('http') != 0 && url.indexOf('http') != 0) return true;
    return false;
}

function chat_decode(item) {
    var content = item.content;
    var content = content.replace(/\n/g, '<br>').replace(/ /g, '&nbsp;').replace(/\[表情(\d+)\]/g, '<img class="face" src="/static/admin/img/emotion/face01/$1.png" title="[表情$1]"/>');
    var message_class, message_type;
    message_class = message_type = 'text';
    // 还原图片
    if (info = /\!\[.*?\]\(([^\)]+?)\)/.exec(content)) {
        url = info[1];
        if (!is_dangerous_url(url)) {
            content = '<img src="' + url + '" />';
            message_class = 'picture';
        }
    } else if (info = /^file\[(.*?)[\t|\|](.*?)]\((.+?)\)$/.exec(content)) {
        // 文件 file[文件名\tescape](文件下载地址)
        var file_name = info[1];
        var file_info = info[2];
        url = info[3];
        if (!is_dangerous_url(url)) {
            var ext_map = {
                ppt: 'ppt',
                pptx: 'ppt',
                doc: 'doc',
                docx: 'doc',
                xls: 'xls',
                xlsx: 'xls',
                pdf: 'pdf',
                txt: 'txt',
                zip: 'zip',
                rar: 'zip'
            };
            var index = file_name.lastIndexOf(".");
            var ext = index ? file_name.substring(index + 1, file_name.length) : "file";
            ext = ext_map[ext] || 'file';
            content = '<div class="file-cover">\
                     <i class="file-icon ' + ext + '-icon"></i>\
                  </div>\
                  <div class="file-info">\
                      <span class="name"><a href="' + url + '" download="' + file_name + '">' + file_name + '</a></span>\
                      <span class="size">' + file_info + '</span>\
                  </div>';
        }
        // 语音消息
    } else if (/^voice\(([^\)]+?)\)$/.test(content)) {
        message_type = 'voice';
        /^voice\(([^\)]+?)\)$/.exec(content);
        var src = RegExp.$1;
    } else if (/\[.*?\]\(([^\)]+?)\)/.test(content)) {
        // 替换a连接，为了避免javascript注入，必须以http或者/开头，否则认为是无效连接
        content = content.replace(/\[(.*?)\]\(((?=http|\/)[^\)]+?)\)/g, '<a href="$2" target="_blank">$1</a>');
    }

    var chat_item = '';

    if(new Date().getTime() > item.timestamp && item.timestamp - (chat_decode.lastTime||0) > 60){
        chat_item = '<li class="time"><span>'+ chat_date(item.timestamp) +'</span></li>';
        chat_decode.lastTime = item.timestamp;
    }

    var target = item.to_avatar ? '<a href="javascript:void(0);" class="avatar"> <img src="/static/admin/img/icon__arrow.png"></a><a href="javascript:void(0);" class="avatar">\
                                        <img src="'+item.to_avatar+'" class="gray">\
                                     </a>\
                                     <div class="target" >\
                                         <p>'+item.to_name+'</p>\
                                     </div>' : '';

    if (item.sub_type == 'notice') {
        return '<li class="notice"><span>'+content+'</span></li>';
    } else if(message_type == 'text') {
        return chat_item+'<li class="others">\
                    <a href="javascript:void(0);" class="avatar">\
                        <img src="'+item.avatar+'">\
                    </a>\
                    <div class="content"><p class="author">'+item.name+'</p>\
                        <div class="msg '+message_class+'">'+content+'</div>\
                    </div>'+ target +'\
                </li>';

    } else if(message_type == 'voice') {
        return chat_item+'<li class="others">\
                        <a href="javascript:void(0);" class="avatar">\
                            <img src="'+item.avatar+'">\
                        </a>\
                        <div class="content"><p class="author">'+item.name+'</p>\
                            <div class="msg text" onclick="audio_paly(this)">\
                                <span>\
                                    <span class="audio_body f-right t-right"></span>\
                                    <span class="audio_box f-left "></span>\
                                    <audio preload="auto" hidden="true" onplay="audio_set_state(this,event)" onended="audio_set_state(this,event)" onpause="audio_set_state(this,event)" onabort="audio_set_state(this,event)" onerror="audio_set_state(this,event)" onstalled="audio_set_state(this,event)" onempted="audio_set_state(this,event)" ondurationchange="audio_durationchange(this)"><source src="'+src+'" type="audio/mpeg"></audio>\
                                </span>\
                            </div>\
                        </div>'+ target +'\
                    </li>';
    }
}

function audio_set_state(that, e) {
    var type = e.type == 'play' ? 'running' : 'paused';
    var dom = $(that).prev();
    dom.css('animation-play-state', type);
    if (type == 'running') {
        if (!dom.hasClass('audio_playing')) {
            dom.addClass('audio_playing');
        }
    } else {
        dom.removeClass('audio_playing');
    }
}

function audio_durationchange(that) {
    $(that).prev().prev().html(Math.ceil(that.duration) + '"');
}

function audio_paly(that) {
    var audio = $(that).find('audio');
    audio = audio.length ? audio[0] : {};
    if (audio.paused) {
        audio.currentTime = 0;
        audio.play();
    }
}

function chat_date(timestamp) {
    var digit = function (num) {
        return num < 10 ? '0' + (num | 0) : num;
    };
    var d = new Date(timestamp * 1000 || new Date());
    return digit(d.getMonth() + 1) + '-' + digit(d.getDate())
        + ' ' + digit(d.getHours()) + ':' + digit(d.getMinutes());
}

function html_encode(html) {
    var temp = document.createElement("div");
    (temp.textContent != undefined ) ? (temp.textContent = html) : (temp.innerText = html);
    var output = temp.innerHTML;
    temp = null;
    return output;
}

function account_edit() {
    layui.use(['layer'], function () {
        layer.open({
            type: 2,
            title: '修改密码',
            content: '/admin/account/edit',
            area: ['400px', '300px']
        });
    });
    return false;
}
