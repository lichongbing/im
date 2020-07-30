$(function() {
	cpcccim = {
		data : {
			chatting : {},
			friend : [],
			group : [],
			mine : [],
			setting : [],
			show : ("none"),
			networkState : (""),
			dataLoading : false,
			baseUri : ("/im/h5/"),
			api : {
				baseInfo : ("/h5/pop/get"),
				deleteChat : ("/h5/pop/delchat"),
				userJoin : ("/h5/login/join"),
				userDetail : ("/h5/user/detail"),
				userUpdate : ("/h5/user/update"),
				getUserByName : ("/h5/user/getbyname"),
				loginCheck : ("/h5/login/check"),
				loginCaptcha : ("/h5/login/logincaptcha"),
				logout : ("/h5/login/logout"),
				joinCaptcha : ("/h5/login/joincaptcha"),
				messageSend : ("/h5/message/send"),
				messageRevoke : ("/h5/message/revoke"),
				messageGet : ("/h5/message/get"),
				messageUnreadCount : ("/h5/message/unreadcount"),
				updateLastReadTime : ("/h5/message/updateLastReadTime"),
				uploadImage : ("/h5/upload/img"),
				uploadFile : ("/h5/upload/file"),
				uploadAvatar : ("/h5/upload/avatar"),
				friendList : ("/h5/friend/getlist"),
				friendApply : ("/h5/friend/apply"),
				friendApplyList : ("/h5/friend/applylist"),
				friendApplyDetail : ("/h5/friend/applydetail"),
				friendApplyCount : ("/h5/friend/applycount"),
				friendAgree : ("/h5/friend/agree"),
				friendRefuse : ("/h5/friend/refuse"),
				friendDelete : ("/h5/friend/delete"),
				friendRemark : ("/h5/friend/remark"),
				friendUpdate : ("/h5/friend/update"),
				groupCreate : ("/h5/group/create"),
				groupDetail : ("/h5/group/detail"),
				groupForbiddenUser : ("/h5/group/forbiddenuser"),
				groupLeave : ("/h5/group/leave"),
				groupDelete : ("/h5/group/delete"),
				groupMemberAdd : ("/h5/group/memberadd"),
				groupMemberDelete : ("/h5/group/memberdel"),
				groupMembers : ("/h5/group/members"),
				groupRemark : ("/h5/group/remark"),
				groupUpdate : ("/h5/group/update"),
				groupMemberUpdate : ("/h5/group/memberupdate"),
				authEndpoint : ("/h5/push/auth"),
				groupForbiddenGroup : ("/h5/group/forbiddengroup"),
				groupChattingGroup : ("/h5/group/chattinggroup"),
				groupuploadAvatar : ("/h5/upload/groupavatar"),
				guaUpdate : ("/h5/group/guaUpdate"),
			}
		},
		isback : false,
		connecter : null,
		show : function(e) {
			if (e != ("home") && e != ("login") && e != ("join") && !cpcccim.data.mine.uid) {
				var t = cpcccim.context.path;
				page.replace(cpcccim.data.baseUri);
				cpcccim.redirectTo = t;
				return
			}
			
			
			
			if (cpcccim.isback && cpcccim.data.show != e) {
				var a = $(("#") + cpcccim.data.show);
				a.addClass(("cpcp__slide-out"));
				a.off(("animationend webkitAnimationEnd"));
				a.on(("animationend webkitAnimationEnd"), function() {
					a.removeClass(("cpcp__slide-out"));
					cpcccim.data.show = e
				})
			} else {
				cpcccim.data.show = e
			}
			var i = $(("#") + e);
			if (!cpcccim.isback && ("home") != e) {
				i.addClass(("cpcp__slide-in"));
				i.off(("animationend webkitAnimationEnd"));
				i.on(("animationend webkitAnimationEnd"), function() {
					i.removeClass(("cpcp__slide-in"));
					i.css(("display"), ("block"))
				})
			}
			cpcccim.isback = false
		},
		chat : function(e, t) {
			n.type = e;
			n.id = t;
			page(cpcccim.data.baseUri + e + ("/chat/") + t)
		},
		promptTone : function() {
			if (cpcccim.data.setting.prompt_tone != ("on"))
				return;
			var e = document.createElement(("audio"));
			e.src = ("/cssjs/sound/default.mp3");
			e.play()
		},
		
		
		
		connect : function() {
			var e = cpcccim.data.setting.ws_address;
			var a = e.split((":"));
			var i = a[1].replace(("//"), (""));
			var o = cpcccim.data.setting.appkey;
			cpcccim.connecter = new Woker(o, {
				encrypted : a[0] == ("wss"),
				enabledTransports : [("ws"), ("wss")],
				wsHost : i,
				wssPort : a[2],
				wsPort : a[2],
				authEndpoint : cpcccim.data.api.authEndpoint
			});
			var n = cpcccim.connecter.subscribe(("user-") + cpcccim.data.mine.uid);
			$.each(cpcccim.data.group, function(e, t) {
				cpcccim.listenGroup(t.gid)
			});

			n.on(("woker:subscription_succeeded"), function() {
				if (!cpcccim.lastUpdateTime) {
					cpcccim.lastUpdateTime = (new Date).getTime();
					return
				}
				var e = (new Date).getTime();
				if (e - cpcccim.lastUpdateTime < 2e3) {
					return
				}
				t.syncData();
				cpcccim.lastUpdateTime = e
			});
			n.on(("message"), cpcccim.onMessage);
			n.on(("revoke"), cpcccim.revokeMessage);
			n.on(("friendApply"), function(e) {
				t.syncApplyCount();
				h.syncApplyList()
			});
			n.on(("addFriend"), function(e) {
				cpcccim.addFriend(e)
			});
			n.on(("removeFriend"), function(e) {
				cpcccim.deleteFriend(e.uid)
			});
			n.on(("addGroup"), function(e) {
				cpcccim.listenGroup(e.gid)
			});
			n.on(("removeGroup"), function(e) {
				cpcccim.deleteGroup(e.gid)
			});
			var p = cpcccim.connecter.subscribe(("global"));
			p.on(("settingChange"), function(e) {
				cpcccim.data.setting = e
			});
			cpcccim.connecter.connection.on(("state_change"), function(e) {
				cpcccim.data.networkState = e.current
			})
		},
		ajax : function(e) {
			var t = e.success || false;
			var a = e.error || false;
			var i = (e.type || ("get") ).toLowerCase();
			var o = typeof e.tip == ("undefined") ? 2 : e.tip;
			var n = typeof e.msg == ("undefined") ? 1 : e.msg;
			if (o) {
				if (i == ("post")) {
					b.dataPosting = 1
				} else {
					cpcccim.data.dataLoading = true;
				}
			} else {
				delete e.tip
			}
			e.success = function(e) {
				if (i == ("post")) {
					if (o > 1 && e.code == 0) {
						b.dataPosting = 2;
						setTimeout(function() {
							b.dataPosting = false
						}, 1e3)
					} else {
						b.dataPosting = false
					}
				} else {
					cpcccim.data.dataLoading = false
				}
				switch(e.code) {
					case-1
					:
						cpcccim.redirectTo = false;
						location = cpcccim.data.baseUri + ("user/login");
						return;
					case-2:
						page(cpcccim.data.baseUri + ("user/login"));
						f.msg(e.msg);
						return
				}
				if (n && e.code > 0) {
					f.msg(e.msg)
				}
				t && t(e)
			};
			e.error = function(e) {
				if (i == ("post")) {
					b.dataPosting = false
				} else {
					cpcccim.data.dataLoading = false
				}
				a && a(e)
			};
			$.ajax(e)
		},
		onMessage : function(e) {
			var t = e.type == ("group") || e.from == cpcccim.data.mine.uid ? e.to : e.from;
			if (!cpcccim.data.chatting[e.type + t]) {
				cpcccim.addChatting({
					id : t,
					name : t == e.to ? e.to_name : e.from_name,
					avatar : t == e.to ? e.to_avatar : e.from_avatar,
					type : e.type,
					unread_count : 0,
					items : []
				})
			}
			var a = e.type == ("group") ? cpcccim.getGroupFromLocal(t) : cpcccim.getFriendFromLocal(t);
			a = a || {};
			cpcccim.data.chatting[e.type + t].items.push({
				mid : e.mid,
				from : e.from,
				name : a.remark || e.from_name,
				to : e.to,
				avatar : e.from_avatar,
				content : e.content,
				timestamp : e.timestamp,
				sub_type : e.sub_type || ("message")
			});
			n.scrollBottom();
			if (e.from != cpcccim.data.mine.uid && e.sub_type != ("notice") && (cpcccim.data.show != ("chat") || n.type != e.type || n.id != t)) {
				if (cpcccim.data.chatting[e.type + t].unread_count != ("99+")) {
					cpcccim.data
					.chatting[e.type + t].unread_count++;
					if (cpcccim.data.chatting[e.type + t].unread_count >= 100)
						cpcccim.data.chatting[e.type + t].unread_count = ("99+")
				}
			}
			if (e.from != cpcccim.data.mine.uid && e.sub_type != ("notice")) {
				cpcccim.promptTone()
			}
		},
		revokeMessage : function(e) {
			var t = e.type;
			var a = t != ("friend") || e.uid == cpcccim.data.mine.uid ? e.id : e.uid;
			var i = e.mid;
			if (cpcccim.data.chatting[t + a]) {
				if (cpcccim.data.chatting[t + a].items) {
					for (var o in cpcccim.data.chatting[t + a].items) {
						var n = cpcccim.data.chatting[t+a]
						.items[o];
						if (n.mid == i) {
							cpcccim.data.chatting[t+a].items[o].sub_type = ("notice");
							cpcccim.data.chatting[t+a].items[o].content = ("此消息已撤回");
							return
						}
					}
				}
			}
		},
		syncChatting : function(e, t) {
			if (!t) {
				console.trace(("id undefined"));
				return
			}
			var a = e == ("friend") ? cpcccim.getFriendFromLocal(t) : cpcccim.getGroupFromLocal(t);
			if (!a) {
				cpcccim.addChatting({
					id : t,
					type : e
				});
				cpcccim.ajax({
					url : e == ("friend") ? cpcccim.data.api.userDetail : cpcccim.data.api.groupDetail,
					type : ("get"),
					data : {
						uid : t,
						gid : t,
						simple : 1
					},
					success : function(a) {
						if (a.code == 0) {
							cpcccim.addChatting({
								id : t,
								name : e == ("friend") ? a.data.remark || a.data.nickname : a.data.remark || a.data.groupname,
								avatar : a.data.avatar,
								type : e
							})
						}
					}
				})
			} else {
				a = {
					id : t,
					name : a.name,
					avatar : a.avatar,
					type : e
				};
				cpcccim.addChatting(a);
				return a
			}
		},
		addChatting : function(e) {
			if (!cpcccim.data.chatting[e.type + e.id]) {
				Vue.set(cpcccim.data.chatting, e.type + e.id, {
					id : e.id,
					name : e.name,
					avatar : e.avatar,
					type : e.type,
					unread_count : e.unread_count || 0,
					items : e.items || []
				})
			} else {
				$.each(cpcccim.data.chatting[e.type + e.id], function(t, a) {
					if ( typeof e[t] != ("undefined")) {
						cpcccim.data.chatting[e.type+e.id][t] = e[t]
					}
				})
			}
			cpcccim.syncUnreadCount(e.type, e.id)
		},
		deleteChatting : function(e, t) {
			console.log(e + t, cpcccim.data.chatting);
			if (cpcccim.
			data.chatting[e + t]) {
				Vue.delete(cpcccim.data.chatting, e + t)
			}
			if (cpcccim.data.show == ("chat") && n.type == e && n.id == t) {
				history.back()
			}
		},
		syncUnreadCount : function(e, t) {
			cpcccim.ajax({
				url : cpcccim.data.api.messageUnreadCount,
				type : ("get"),
				data : {
					type : e,
					id : t
				},
				success : function(a) {
					if (a.code == 0) {
						if (cpcccim.data.chatting[e + t]) {
							cpcccim.data.chatting[e+t][("unread_count")] = a.data
						}
					}
				}
			})
		},
		syncUser : function(e) {
			cpcccim.ajax({
				url : cpcccim.data.api.userDetail,
				type : ("get"),
				data : {
					uid : e
				},
				success : function(t) {
					if (t.code == 0) {
						$.each(cpcccim.data.friend, function(a, i) {
							if (i[e]) {
								cpcccim.data.friend[a][e].name = t.data.remark || t.data.nickname;
								cpcccim.data.friend[a][e].avatar = t.data.avatar
							}
						});
						if (cpcccim.data.chatting[("friend") + e]) {
							cpcccim.data.chatting[("friend") + e].avatar = t.data.avatar;
							cpcccim.data.chatting[("friend") + e].name = t.data.remark || t.data.nickname
						}
					}
				}
			})
		},
		syncGroup : function(e) {
			cpcccim.ajax({
				url : cpcccim.data.api.groupDetail,
				type : ("get"),
				data : {
					gid : e,
					simple : 1
				},
				success : function(t) {
					if (t.code == 0) {
						var a = false;
						$.each(cpcccim.data.group, function(i, o) {
							if (o.gid == e) {
								cpcccim
								.data.group[i].name = t.data.remark || t.data.groupname;
								cpcccim.data.group[i].avatar = t.data.avatar;
								cpcccim.data.group[i].uid = t.data.uid;
								a = true
							}
						});
						if (!a) {
							cpcccim.data.group.push({
								gid : e,
								avatar : t.data.avatar,
								name : t.data.remark || t.data.groupname,
								uid : t.data.uid
							})
						}
						if (cpcccim.data.chatting[("group") + e]) {
							cpcccim.data.chatting[("group") + e].avatar = t.data.avatar;
							cpcccim.data.chatting[("group") + e].name = t.data.remark || t.data.groupname
						}
					}
				}
			})
		},
		addFriend : function(e) {
			cpcccim.addChatting({
				id : e.uid,
				name : e.name,
				avatar : e.avatar,
				type : ("friend"),
				key : ("friend") + e.uid,
				unread_count : 0,
				items : []
			});
			cpcccim.syncAddress()
		},
		updateGroup : function(e, t) {
			if (cpcccim.data.chatting[("group") + e]) {
				for (var a in t) {
					if ( typeof cpcccim.data.chatting[ ("group") +e][a] != ("undefined")) {
						Vue.set(cpcccim.data.chatting[("group") + e], a, t[a])
					}
				}
			}
		},
		updateFriend : function(e, t) {
			if (cpcccim.data.chatting[("friend") + e]) {
				for (var a in t) {
					if ( typeof cpcccim.data.chatting[ ("frien"+"d") +e][a] != ("undefined")) {
						Vue.set(cpcccim.data.chatting[("friend") + e], a, t[a])
					}
				}
			}
			cpcccim.syncAddress()
		},
		deleteFriend : function(e) {
			cpcccim.deleteChatting(("friend"), e);
			cpcccim.deleteFriendFromLocal(e)
		},
		deleteGroup : function(e) {
			cpcccim.unlistenGroup(e);
			cpcccim.deleteChatting(("group"), e);
			cpcccim.deleteGroupFromLocal(e)
		},
		syncAddress : function() {
			cpcccim.ajax({
				url : cpcccim.data.api.friendList,
				type : ("get"),
				success : function(e) {
					if (e.code == 0) {
						Vue.set(cpcccim.data, ("friend"), e.data)
					}
				}
			})
		},
		getFriendFromLocal : function(e) {
			for (var t in cpcccim.data.friend
			) {
				var a = cpcccim.data.friend[t];
				if (a[e])
					return a[e]
			}
		},

		deleteFriendFromLocal : function(e) {
			for (var t in cpcccim.data.friend) {
				var a = cpcccim.data.friend[t];
				if (a[e]) {
					Vue.delete(cpcccim.data.friend[t], e);
					if (!Object.values(cpcccim.data.friend[t]).length) {
						Vue.delete(cpcccim.data.friend, t)
					}
					return
				}
			}
		},
		getGroupFromLocal : function(e) {
			for (var t in cpcccim.data.group) {
				if (cpcccim.data.group[t].gid == e)
					return cpcccim.data.group[t]
			}
		},
		deleteGroupFromLocal : function(e) {
			for (var t in cpcccim.data.group) {
				if (cpcccim.data.group[t].gid == e) {
					Vue.delete(cpcccim.data.group, t);
					return
				}
			}
		},
		listenGroup : function(e) {
			var t = ("group-") + e;
			if ( typeof cpcccim.connecter.channels.channels[t] != ("undefined")) {
				return
			}
			var a = cpcccim.connecter.subscribe(t);
			a.on(("message"), cpcccim.onMessage);
			a.on(("updateGroup"), function(e) {
				var t = e.gid;
				cpcccim.updateGroup(t, e)
			});
			a.on(("removeGroup"), function(e) {
				cpcccim.deleteGroup(e.gid)
			});
			a.on(("update"), function(t) {
				cpcccim.updateGroup(e, t)
			});
			a.on(("revoke"), cpcccim.revokeMessage)
		},
		unlistenGroup : function(e) {
			cpcccim.connecter.unsubscribe(("group-") + e)
		},
		check : function(e) {
			if (cpcccim.backToPath) {
				if (cpcccim.backToPath.charAt(cpcccim.backToPath.length - 1) == ("/")) {
					cpcccim.backToPath = cpcccim.backToPath.substr(0, cpcccim.backToPath.length - 1)
				}
				if (cpcccim.backToPath != e.path && cpcccim.backToPath + ("/") != e.path) {
					console.log(("backToPath:") + cpcccim.backToPath);
					history.back();
					return false
				}
				cpcccim.backToPath = ("")
			}
			cpcccim.context = e;
			return true
		},
		setting : function(e, t, a, i, o) {
			u.title = e;
			u.desc = t;
			u.btn_name = i;
			u.content = cpcccim.htmlDecode(a);
			u.callback = o ? o : null;
			cpcccim.show(("setting"))
		},
		autoUpdateTime : function() {
			$.each($((".cpcp__time")), function(e, t) {
				var a = $(t);
				a.html(Vue.prototype.$formatTime(a.attr(("value"))))
			})
		},
		checkUsername : function(e) {
			var t = /^[A-Za-z0-9]{1,60}$/;
			return t.test(e)
		},
		isDangerousUrl : function(e) {
			e = e.toLowerCase();
			if (e.indexOf(("javascript")) != -1)
				return true;
			if (e.indexOf(("/")) != 0 && e.indexOf(("http")) != 0 && e.indexOf(("http")) != 0)
				return true;
			return false
		},
		textContent : function(e) {
			e = e.replace(/\[表情\d+\]/g, ("[表情]"));
			if (/\!\[.*?\]\(([^\)]+?)\)/.test(e)) {
				e = ("[图片]")
			} else if (/^file\[(.*?)[\t|\|](.*?)]\((.+?)\)$/.test(e)) {
				e = ("[文件]")
			} else if (/\[.*?\]\(([^\)]+?)\)/.test(e)) {
				e = e.replace(/\[(.*?)\]\(([^\)]+?)\)/g, ("$1"))
			} else if (/^voice\(([^\)]+?)\)$/.test(e)) {
				e = ("[语音]")
			}
			return e
		},
		htmlEncode : function(e) {
			var t = {
				"&" : ("&amp;"),
				"<" : ("&lt;"),
				">" : ("&gt;"),
				'"' : ("&quot;"),
				"'" : ("&#039;")
			};
			return e.replace(/[&<>"']/g, function(e) {
				return t[e]
			})
		},
		htmlDecode : function(e) {
			var t = {
				"&amp;" : ("&"
				),
				"&lt;" : ("<"),

				"&gt;" : (">"),
				"&quot;" : ('"'),
				"&#039;" : ("'")
			};
			return e.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, function(e) {
				return t[e]
			})
		},
		scrollIntoView : function(e) {
			var t = e;
			e = e == ("#") ? "other" : e;
			$.each($((".cpcp__firend_list_") + e), function(e, t) {
				t.scrollIntoView()
			});
			$((".cpcp__friend-list-showletter")).text(t).fadeIn(300);
			setTimeout(function() {
				$((".cpcp__friend-list-showletter"
				)).fadeOut(300)
			}, 500)
		},
		resizeWindow : function() {
			var e = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
			var t = parseFloat(document.documentElement.style.fontSize);
			cpcccim.rem = t;
			$((".cpcp__chat-wrapper")).css(("height"), e);
			$((".cpcp__main-page")).css(("height"), e);
			$((".cpcp__body")).css(("height"), e - 2.1 * t)
		}
	};
	var e = new Vue({
		el : ("#welcome"),
		data : {
			cpcccim : cpcccim.data
		}
	});
	var t = new Vue({
		el : ("#home"),
		data : {
			cpcccim : cpcccim.data,
			tab : ("chatting"),
			unreadApplyCount : 0,
			slideDistance : 0
		},
		watch : {
			tab : function(e, t) {
				if (e == ("friend") && !this.hasSyncAddress) {
					cpcccim.syncAddress();
					this.hasSyncAddress = true
				}
			}
		},
		computed : {
			unreadMessageCount : function() {
				var e = 0;
				$.each(cpcccim.data.chatting, function(t, a) {
					if (e == ("99+"))
						return;
					if (a.unread_count == ("99+")) {
						e = ("99+");
						return
					}
					e += a.unread_count
				});
				return e
			},
			chattingItems : function() {
				return Object.values(this.cpcccim.chatting).sort(function(e, t) {
					var a = e.items.length && e.items.slice(-1)[0];
					var i = t.items.length && t.items.slice(-1)[0];
					var o = a && a.timestamp ? a.timestamp : 0;
					var n = i && i.timestamp ? i.timestamp : 0;
					return o > n ? -1 : o < n ? 1 : 0
				})
			},
			stateName : function() {
				var e = {
					initialized : (""),
					connected : (""),
					connecting : ("连接中..."),
					unavailable : ("网络暂时不可用，稍后重试"),
					failed : ("连接断开，请点击刷新"),
					disconnected : ("连接断开")
				};
				return e[cpcccim.data.networkState]
			}
		},
		methods : {
			refresh : function() {
				window.location.reload()
			},
			syncData : function() {
				cpcccim.ajax({
					url : cpcccim.data.api.baseInfo,
					type : ("get"),
					success : function(e) {
						switch(e.code) {
							case 0:
								cpcccim.data.mine = e.data.mine;
								$.each(e.data.chatting, function(e, t) {
									var a = cpcccim.data.chatting[e] ? cpcccim.data.chatting[e].items : [];
									Vue.set(cpcccim.data.chatting, e, t);
									if (!a.length || !t.items.length) {
										return
									}
									var i = t.items[0].mid;
									if (i > a[a
									.length-1][("mid")]) {
										a.push(t.items[0])
									}
									cpcccim.data.chatting[e].items = a
								});
								n.getmessage(true, null, true);
								cpcccim.data.friend = e.data.friend;
								cpcccim.data.group = e.data.group;
								cpcccim.data.setting = e.data.setting;
								t.unreadApplyCount = e.data.mine.unread_friend_apply_count;
								if (!cpcccim.connectionInited) {
									cpcccim.connectionInited = true;
									cpcccim.connect()
								}
								if (cpcccim.redirectTo) {
									page(cpcccim.redirectTo);
									cpcccim.redirectTo = ("")
								}
								return;
							case
							101:
								page(cpcccim.data.baseUri + ("user/login"
								));
								return
						}
					}
				})
			},
			syncApplyCount : function() {
				cpcccim.ajax({
					url : cpcccim.data.api.friendApplyCount,
					type : ("get"
					),
					success : function(e) {
						if (e.code == 0) {
							t.unreadApplyCount = e.data
						}
					}
				})
			},
			lastMessage : function(e) {
				e = e.items.length && e.items
				.slice(-1)[0];
				if (!e) {
					return {
						content : (""),
						timestamp : 0
					}
				}
				return {
					content : cpcccim.textContent(e[("content")]),
					timestamp : e[("timestamp")]
				}
			},
			touchStart : function(e, t) {
				t.startX = e.touches[0].clientX;
				t.startY = e.touches[0].clientY;
				this.restSlide(t)
			},
			touchMove : function(e, t) {
				var a = e.changedTouches[0].clientX;
				var i = e.changedTouches[0].clientY;
				if (!t.direction) {
					if (Math.abs(a - t.startX) > Math.abs(i - t.startY)) {
						t.direction = ("left-right")
					} else {
						t.direction = ("up-down")
					}
				}
				if (t.direction == ("up-down")) {
					return
				}
				var o = (a - t.startX) * -1 / cpcccim.rem;
				if (!t.slideDistance && o < 0) {
					return
				} else if (t.slideDistance == 2 && o > 0) {
					return
				}
				if (o > 0) {
					Vue.set(t, ("slideDistance"), o > 2 ? 2 : o)
				} else {
					Vue.set(t, ("slideDistance"), o < -2 ? 0 : 2 + o)
				}
				e.preventDefault()
			},
			touchEnd : function(e, t) {
				if (t.direction == ("left-right")) {
					var a = e.changedTouches[0].clientX;
					if (t.startX - a > 30) {
						Vue.set(t, ("slideDistance"
						), 2)
					} else {
						Vue.set(t, ("slideDistance"), 0)
					}
					if (t.startX - a < -30) {
						Vue.set(t, ("slideDistance"), 0)
					}
				}
				t.direction = false;
				t.startX = 0;
				t.startY = 0
			},
			restSlide : function(e) {
				$.each(this.chattingItems, function(t, a) {
					if (e == a) {
						return
					}
					Vue.set(a, ("slideDistance"), 0)
				})
			},
			deleteItem : function(e, t) {
				cpcccim.ajax({
					url : cpcccim.data.api.deleteChat,
					type : ("post"),
					data : {
						type : t.type,
						id : t.id
					},
					success : function(e) {
						if (e.code == 0) {
							cpcccim.deleteChatting(t.type, t.id)
						}
					}
				})
			},
			showTestInfo : function() {
				f.msg(("扩展功能入口，开发者可以自行扩展"))
			},
			scrollIntoView : cpcccim.scrollIntoView
		}
	});
	var a = function(e) {
		if (!cpcccim.check(e))
			return;
		if (!cpcccim.data.mine.uid) {
			t.syncData()
		}
		cpcccim.show(("home"))
	};
	page(cpcccim.data.baseUri, a);
	var i = new Vue({
		el : ("#login"),
		data : {
			cpcccim : cpcccim.data,
			username : (""),
			password : (""),
			captcha : ("")
		},
		methods : {
			login : function() {
				if (this.username == ("")) {
					return f.msg(("用户名不能为空！"))
				} else if (this.password == ("")) {
					return f.msg(("密码不能为空！"))
				} else if (this.captcha == ("")) {
					return f.msg(("验证码不能为空！"))
				} else {
					var e = this;
					cpcccim.ajax({
						url : cpcccim.data.api.loginCheck,
						type : ("post"),
						tip : 0,
						data : {
							username : this.username,
							password : this.password,
							captcha : this.captcha
						},
						success : function(t) {
							if (t.code == 0) {
								page(cpcccim.data.baseUri)
							} else {
								e.switchCaptcha()
							}
						}
					})
				}
			},
			switchCaptcha : function() {
				this.$refs.captchaImg.src = cpcccim.data.api.loginCaptcha + ("?rand=") + Math.random()
			}
		}
	});
	var o = new Vue({
		el : ("#join"),
		data : {
			cpcccim : cpcccim.data,
			username : (""),
			nickname : (""),
			password : (""),
			sign : (""),
			captcha : ("")
		},
		methods : {
			join : function() {
				if (this.username == ("")) {
					return f.msg(("用户名不能为空！"))
				} else if (this.nickname == ("")) {
					return f.msg(("昵称不能为空！"))
				} else if (this.password == ("")) {
					return f.msg(("密码不能为空！"))
				} else if (this.captcha == ("")) {
					return f.msg(("验证码不能为空！"))
				} else {
					if (!cpcccim.checkUsername(this.username)) {
						return f.msg(("用户名只能包含字母和数字，长度小于60"))
					}
					var e = this;
					cpcccim.ajax({
						url : cpcccim.data.api.userJoin,
						type : ("post"
						),
						tip : 0,
						data : {
							username : this.username,
							password : this.password,
							nickname : this.nickname,
							sign : this.sign,
							captcha : this.captcha
						},
						success : function(t) {
							if (t.code == 0) {
								page.replace(cpcccim.data.baseUri + ("user/login"));
								return
							} else {
								e.switchCaptcha()
							}
						}
					})
				}
			},
			switchCaptcha : function() {
				this.$refs.captchaImg.src = cpcccim.data.api.joinCaptcha + ("?rand=") + Math.random()
			}
		}
	});
	page(cpcccim.data.baseUri + ("friend/chat/:uid"), function(e) {
		if (!cpcccim.check(e))
			return;
		n.id = e.params.uid;
		n.type = ("friend");
		cpcccim.show(("chat"));
		cpcccim.syncUser(n.id);
		n.getmessage(true, null, true)
	});
	page(cpcccim.data.baseUri + ("group/chat/:gid"), function(e) {
		if (!cpcccim.check(e))
			return;
		n.id = e.params.gid;
		n.type = ("group");
		cpcccim.show(("chat"));
		cpcccim.syncGroup(n.id);
		n.getmessage(true, null, true)
	});
	var n = new Vue({
		el : ("#chat"),
		data : {
			cpcccim : cpcccim.data,
			id : 0,
			type : ("friend"),
			message : (""),
			voice : false,
			panel : false,
			voiceTips : (""),
			voiceBtnTips : ("按住 说话"),
			messageLock : false,
			messageEnd : false,
			tapMenuShow : false,
			tapMenuLeft : 0,
			tapMenuTop : 0,
			supportRevoke : false
		},
		computed : {
			messageList : function() {
				this.lastInsertTime = this.lastTime = 0;
				if (!this.
				cpcccim.chatting[this.type + this.id]) {
					return []
				}
				return this.cpcccim.chatting [this.
				type+this.id][("items")] || []
			},
			name : function() {
				if (!this.id)
					return "";
				var e = this.cpcccim.chatting[this.type + this.id] || false;
				return e ? e.remark || e.name : ("")
			},
			inputWidth : function() {
				var e = 6.1;
				if (this.cpcccim.setting.voice == ("on")) {
					e -= .88
				}
				if (this.cpcccim.setting.emoji == ("on")) {
					e -= .88
				}
				return e + ("rem")
			},
			mid : function() {
				var e = this.cpcccim.chatting[this.type + this.id];
				if (!e || !e.items || !e.items.length) {
					return null
				}
				return e.items [0][("mid")] || null
			},
			supportCopy : function() {
				return typeof document.execCommand == ("function")
			}
		},
		methods : {
			reset : function() {
				cpcccim.ajax({
					url : cpcccim.data.api.updateLastReadTime,
					type : ("post"),
					tip : 0,
					data : {
						id : this.id,
						type : this.type
					}
				});
				if (this.cpcccim.chatting[this.type + this.id] && this.cpcccim.chatting[this
				.type+this.id][("items")]) {
					this
					.cpcccim.chatting[this.type + this.id].items = this.
					cpcccim.chatting[this.type + this.id].items.slice(-20);
					this.cpcccim.chatting[this.type + this.id].unread_count = 0
				}
				this.voiceTips = this.message = ("");
				this.messageEnd = this.messageLock = this.panel = this.voice = false;
				this.voiceBtnTips = ("按住 说话");
				this.lastTime = this.lastInsertTime = 0
			},
			needInsertTime : function(e) {
				if (e < this.lastTime) {
					this.lastInsertTime = 0
				}
				this.lastTime = e;
				if ((new Date
				).getTime() > e && e - this.lastInsertTime > 60) {
					this.lastInsertTime = e;
					return true
				}
				return false
			},
			send : function() {
				this.$refs.input.focus();
				this.panel = false;
				if (this.message == ("")) {
					return
				}
				that = this;
				cpcccim.ajax({
					url : cpcccim.data.api.messageSend,
					type : ("post"),
					tip : 0,
					data : {
						to : this.id,
						content : this.message,
						type : this.type
					},
					success : function(e) {
					},
					error : function() {
						f.msg(("发送失败，网络/服务器不可用"))
					}
				});
				that.message = ("")
			},
			toggleVoice : function() {
				this.voice = !this.voice;
				if (this.voice) {
					if (navigator.mediaDevices === undefined) {
						navigator.mediaDevices = {}
					}
					if (navigator.mediaDevices.getUserMedia === undefined) {
						navigator.mediaDevices.getUserMedia = function(e) {
							var t = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
							if (!t) {
								var a = new Error;
								a.name = ("当前浏览器不支持语音");
								return Promise.reject(a)
							}
							return new Promise(function(a, i) {
								t.call(navigator, e, a, i)
							})
						}
					}
					navigator.mediaDevices.getUserMedia({
						audio : true
					}).then(function(e) {
						e.getTracks().forEach(function(e) {
							e.stop()
						})
					}).catch(function(e) {
						n.voice = false;
						n.showUserMediaError(e)
					})
				}
			},
			openEmotion : function() {
				if (this.panel == ("face")) {
					this.panel = false;
					return
				}
				this.panel = ("face");
				if (!this.emotion_swiper) {
					var e = (""), t = 0, a, i = 100, o = 21;
					for (var n = 0; n <= Math.floor(i / o); n++) {
						e += ('<div class="swiper-slide"><div class="cpcp__face-list">');
						for (var p = 0; p < o; p++) {
							a = t++;
							if (a > i)
								break;
							e += ('<span><img class="face" src="/cssjs/img/emotion/face01/') + a + ('.png" title="[表情') + a + (']"/></span>')
						}
						e += ("</div></div>")
					}
					$(("#cpcp__emotion .swiper-wrapper")).html(e);
					this.$nextTick(function() {
						this.emotion_swiper = new Swiper(("#cpcp__emotion"), {
							pagination : (".cpcp__emotion-pagination"),
							paginationClickable : true
						})
					})
				}
			},
			openFunction : function() {
				this.panel = this.panel == ("function") ? false : ("function")
			},
			closePanel : function() {
				this.panel = false
			},
			selectEmotion : function(e) {
				this.message += e.target.title
			},
			scrollBottom : function(e) {
				this.$nextTick(function() {
					if (e || $((".cpcp__chat-content")).height() - ($((".cpcp__chat-msg-panel")).scrollTop() + $((".cpcp__chat-msg-panel"
					)).height()) < 200) {

						setTimeout(function() {
							$((".cpcp__chat-msg-panel")).scrollTop($((".cpcp__chat-content"
							)).height() + 1e3)
						}, 200)
					}
				})
			},
			readImageAndSend : function(e) {
				var t = e.getAsFile();
				var a = new FormData;
				var i = encodeURIComponent(("截图.png"));
				a.append(("file"), t, i);
				cpcccim.ajax({
					url : cpcccim.data.api.uploadImage,
					type : ("POST"),
					data : a,
					contentType : false,
					processData : false,
					tip : 1,
					success : function(e) {
						if (e.code == 0) {
							var t = ("![图片](") + e.data.src + (")");
							cpcccim.ajax({
								url : cpcccim.data.api.messageSend,
								type : ("post"),
								tip : 0,
								data : {
									to : n.id,
									content : t,
									type : n.type
								},
								success : function(e) {
								}
							})
						}
					}
				})
			},
			paste : function(e) {
				console.log(("paste"));
				var t = e.clipboardData, a = 0, i, o, n;
				if (t) {
					i = t.items;
					if (!i) {
						return
					}
					o = i[0];
					n = t.types || [];
					for (; a < n.length; a++) {
						if (n[a] === ("Files"
						)) {
							o = i[a];
							break
						}
					}
					if (o && o.kind === ("file") && o.type.match(/^image\//i)) {
						this.readImageAndSend(o)
					}
				}
			},
			uploadImage : function(e) {
				this.panel = false;
				var t = new FormData;
				var a = e.target;
				t.append(("file"), a.files[0]);
				cpcccim.ajax({
					url : cpcccim.data.api.uploadImage,
					type : ("POST"),
					data : t,
					contentType : false,
					processData : false,
					tip : 1,
					success : function(e) {
						if (e.code == 0) {
							var t = ("![图片](") + e.data.src + (")");
							cpcccim.ajax({
								url : cpcccim.data.api.messageSend,
								type : ("post"),
								tip : 0,
								data : {
									to : n.id,
									content : t,
									type : n.type
								},
								success : function(e) {
								}
							})
						}
					}
				});
				a.parentNode.reset()
			},
			uploadFile : function(e) {
				this.panel = false;
				var t = new FormData;
				var a = e.target;
				t.append(("file"), a.files[0]);
				cpcccim.ajax({
					url : cpcccim.data.api.uploadFile,
					type : ("POST"),
					data : t,
					contentType : false,
					processData : false,
					tip : 1,
					success : function(e) {
						if (e.code == 0) {
							var t = ("file[") + e.data.name + ("\t") + e.data.size + ("](") + e.data.src + (")");
							cpcccim.ajax({
								url : cpcccim.data.api.messageSend,
								type : ("post"),
								tip : 0,
								data : {
									to : n.id,
									content : t,
									type : n.type
								},
								success : function(e) {
								}
							})
						}
					}
				});
				a.parentNode.reset()
			},	
			Nb_Position : function(e) 
			{
				this.panel = false;
				Map_Select(n.id,n.type);
			},	
			
			Nb_PayRp : function(e) 
			{
				this.panel = false;
				if (n.type=="group"){Pay_Open(3,n.id,cpcccim.data.mine.uid);}else{Pay_Open(2,n.id,cpcccim.data.mine.uid);}
			},	
			
			Nb_PayTa : function(e) 
			{
				this.panel = false;
				Pay_Open(1,n.id,cpcccim.data.mine.uid);
			},	
			beginVoiceReocord : function(e) {
				e.target.posStart = e.touches[0].pageY;
				this.voiceBtnTips = ("松开 发送");
				this.voiceTips = ("手指上划，取消发送");
				try {
					window.AudioContext = window.AudioContext || window.webkitAudioContext;
					this.audio_context = new AudioContext
				} catch(e) {
					f.msg(e);
					return
				}
				navigator.mediaDevices.getUserMedia({
					audio : true
				}).then(function(e) {
					var t = n.audio_context.createMediaStreamSource(e);
					n.recorder = new Recorder(t);
					n.recorder.record()
				}).catch(function(e) {
					n.showUserMediaError(e)
				})
			},
			showUserMediaError : function(e) {
				var t = e.name;
				switch(t) {
					case
					"NotAllowedError" :
					case "PermissionDeniedError" :
						t = e.name + (" 浏览器未获得麦克风权限");
						break;
					case "NotFoundError"
					:
					case "DevicesNotFoundError" :
						t = e.name + ("录音设备未找到");
						break;
					case "NotSupportedError" :
						t = e.name + ("该浏览器不支持录音功能");
						break
				}
				f.msg(t)
			},
			sendVoice : function(e) {
				voiceBtnTips = ("按住 说话");
				if (!this.recorder) {
					return
				}
				if (this.voiceTips == ("松开手指，取消发送")) {
					this.voiceTips = ("");
					this.recorder.stop();
					this.recorder.clear();
					this.audio_context.close();
					return
				}
				this.voiceTips = ("");
				this.recorder.stop();
				this.recorder.exportWAV(function(e) {
					var t = e;
					var a = new FormData;
					var i = encodeURIComponent(("voice_recording_") + (new Date).getTime() + (".wav"));
					a.append(("file"), t, i);
					cpcccim.ajax({
						url : cpcccim.data.api.uploadFile,
						type : ("POST"),
						data : a,
						contentType : false,
						processData : false,
						tip : 1,
						success : function(e) {
							if (e.code == 0) {
								var t = ("voice(") + e.data.src + (")");
								cpcccim.ajax({
									url : cpcccim.data.api.messageSend,
									type : ("post"),
									tip : 0,
									data : {
										to : n.id,
										content : t,
										type : n.type
									},
									success : function(e) {
									}
								})
							}
						}
					});
					n.recorder.clear();
					n.audio_context.close()
				})
			},
			cancelVoice : function(e) {
				e.preventDefault();
				var t = e.targetTouches[0].pageY;
				if (e.target.posStart - t > 50) {
					this.voiceTips = ("松开手指，取消发送");
					this.voiceBtnTips = ("松开 结束")
				} else {
					this.voiceTips = ("手指上划，取消发送");
					this.voiceBtnTips = ("松开 发送")
				}
			},
			getmessage : function(e, t, a) {
				if (this.messageLock || this.messageEnd || !this.id) {
					return
				}
				var i = {
					id : this.id,
					type : this.type
				};
				var o = 20;
				i.limit = o;
				if (!a && this.mid) {
					i.mid = this.mid
				}
				var p = this;
				this.messageLock = true;
				cpcccim.ajax({
					url : cpcccim.data.api.messageGet,
					type : ("get"),
					data : i,
					success : function(i) {
						if (i.code == 0) {
							if (!i.data.length || i.data.length < o) {
								p.messageEnd = true
							}
							if (!n.cpcccim.chatting[n.type + n.id]) {
								cpcccim.syncChatting(n.type, n.id)
							}
							if (!a) {
								$.each(n.cpcccim.chatting[n.type+n.id][("items")], function(e, t) {
									i.data.push(t)
								})
							}
							n.cpcccim.chatting[n.type + n.id].items = i.data;
							if (t) {
								p.$nextTick(t)
							}
							n.scrollBottom(e || false);
							setTimeout(function() {
								p.messageLock = false;
								p.lastInsertTime = p.lastTime = 0
							}, 100)
						}
					},
					error : function() {
						p.messageLock = false
					}
				})
			},
			handleScroll : function(e) {
				this.tapMenuShow = false;
				var t = $(e.target), a = t.scrollTop();
				var i = t.prop(("scrollHeight"));
				var o = function() {
					var e = t.prop(("scrollHeight"));
					var a = e - i;
					a > 0 && t.scrollTop(a)
				};
				if (a <= 0) {
					this.getmessage(false, o)
				}
			},
			showtapMenu : function(e, t, a, i, o, n) {
				this.supportRevoke = true;
				if (o != cpcccim.data.mine.uid) {
					if (t == ("group")) {
						var p = cpcccim.getGroupFromLocal(a);
						if (!p || p.uid != cpcccim.data.mine.uid) {
							this.supportRevoke = false
						}
					} else {
						this.supportRevoke = false
					}
				}
				this.tapMenuShow = true;
				var s = this;
				var r = $(e.currentTarget);
				this.$nextTick(function() {
					var e = r.position();
					var o = $(s.$refs.menu);
					s.$refs.menu.type = t;
					s.$refs.menu.id = a;
					s.$refs.menu.mid = i;
					s.$refs.menu.content = n;
					s.tapMenuLeft = e.left + r.width() / 2 - o.width() / 2 + ("px");
					s.tapMenuTop = e.top - o.height() - 8 + ("px")
				})
			},
			copy : function() {
				this.closeTapMenu(100);
				var e = this.$refs.menu.content;
				function t(e) {
					if (document.selection) {
						var t = document.body.createTextRange();
						t.moveToElementText(e);
						t.select()
					} else if (window.getSelection) {
						var t = document.createRange();
						t.selectNode(e);
						window.getSelection().removeAllRanges();
						window.getSelection().addRange(t)
					}
				}

				var a = document.createElement(("DIV"));
				a.textContent = e;
				document.body.appendChild(a);
				t(a);
				document.execCommand(("copy"));
				a.remove()
			},
			revoke : function() {
				this.closeTapMenu(100);
				if (this.$refs.menu.mid) {
					cpcccim.ajax({
						url : cpcccim.data.api.messageRevoke,
						type : ("post"),
						data : {
							type : this.$refs.menu.type,
							id : this.$refs.menu.id,
							mid : this.$refs.menu.mid
						},
						success : function() {
						}
					})
				}
				this.$refs.menu.mid = this.$refs.menu.type = this.$refs.menu.mid = this.$refs.menu.content = ("")
			},
			closeTapMenu : function(e) {
				if (!e) {
					n.tapMenuShow = false;
					return
				}
				setTimeout(function() {
					n.tapMenuShow = false
				}, e)
			},
			touchStart : function(e) {
				if ($((".cpcp__chat-tap-menu")).has(e.target).length == 0) {
					this.closeTapMenu()
				}
			},
			resizeWindow : function() {
				setTimeout(function() {
					window.scrollTo(0, document.body.scrollTop + 1);
					document.body.scrollTop >= 1 && window.scrollTo(0, document.body.scrollTop - 1)
				}, 10);
				cpcccim.resizeWindow()
			}
		}
	});
	var p = new Vue({
		el : ("#group_list"),
		data : {
			cpcccim : cpcccim.data
		}
	});
	page(cpcccim.data.baseUri + ("group/list"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.show(("group_list"
		))
	});
	var s = new Vue({
		el : ("#user_detail"),
		data : {
			cpcccim : cpcccim.data,
			remarkname : (""),
			username : (""),
			nickname : (""),
			sign : (""),
			avatar : (""),
			isFriend : false,
			uid : 0,
			gid : 0,
			accountState : ("normal")
		},
		methods : {
			chat : function() {
				cpcccim.chat(("friend"), this.uid)
			},
			deleteFriend : function() {
				var e = this.uid;
				l.warningConfirm(('确认将好友"') + (this.remarkname || this.nickname) + ('"删除吗？'), function() {
					cpcccim.ajax({
						url : cpcccim.data.api.friendDelete,
						type : ("post"),
						data : {
							friend_uid : e
						},
						success : function(t) {
							if (t.code == 0) {
								cpcccim.deleteFriend(e);
								history.go(-2)
							}
						}
					})
				})
			}
		}
	});
	page(cpcccim.data.baseUri + ("user/detail/:uid"), r);
	page(cpcccim.data.baseUri + ("user/detail/:uid/gid/:gid"), r);
	function r(e) {
		if (!cpcccim.check(e))
			return;
		var t = e.params.uid;
		s.uid = t;
		s.gid = e.params.gid || 0;
		var a = cpcccim.getFriendFromLocal(t);
		if (a) {
			s.avatar = a.avatar;
			s.nickname = a.name;
			s.remarkname = ("");
			s.username = ("　");
			s.isFriend = true;
			s.sign = (" ")
		}
		cpcccim.ajax({
			url : cpcccim.data.api.userDetail,
			type : ("get"),
			data : {
				uid : t
			},
			success : function(e) {
				if (e.code == 0) {
					s.avatar = e.data.avatar;
					s.nickname = e.data.nickname;
					s.remarkname = e.data.remark;
					s.username = e.data.username;
					s.isFriend = e.data.is_friend;
					s.sign = e.data.sign;
					s.accountState = e.data.account_state
				}
			}
		});
		cpcccim.show(("user_detail"))
	}

	var c = new Vue({
		el : ("#forbidden"),
		data : {
			cpcccim : cpcccim.data,
			selectedValue : 0,
			uid : 0,
			gid : 0
		},
		methods : {
			action : function() {
				if (!this.selectedValue) {
					return
				}
				var e = this.selectedValue;
				cpcccim.ajax({
					url : cpcccim.data.api.groupForbiddenUser,
					type : ("post"),
					data : {
						uid : this.uid,
						gid : this.gid,
						time : e
					},
					success : function(e) {
						if (e.code == 0) {
							history.go(-2)
						}
					}
				})
			}
		}
	});
	page(cpcccim.data.baseUri + ("group/forbidden/:gid/:uid"), function(e) {
		if (!cpcccim.check(e))
			return;
		c.uid = e.params.uid;
		c.gid = e.params.gid;
		cpcccim.show(("forbidden"))
	});
	
	
	
	page(cpcccim.data.baseUri + ("group/detail/:gid"), function(e) {
		if (!cpcccim.check(e))
			return;
		var t = e.params.gid;
		d.gid = t;
		cpcccim.ajax({
			url : cpcccim.data.api.groupDetail,
			type : ("get"),
			data : {gid : t},
			success : function(e) {
				if (e.code == 0) {
					d.members = e.data.members;
					d.groupname = e.data.info.groupname;
					d.remark = e.data.info.remark;
					d.uid = e.data.info.uid
					d.group_state = e.data.info.state
				}
			}
		});
		cpcccim.show(("group_detail"));
		d.members.length=0;m.members.length=0;
	});

	
	var d = new Vue({
		el : ("#group_detail"),
		data : {
			cpcccim : cpcccim.data,
			gid : 0,
			uid : 0,
			groupname : (""),
			remark : (""),
			members : []
		},
		methods : {
			leaveGroup : function() {
				var e = this.gid;
				l.warningConfirm(("确认要退出群组吗？"), function() {
					cpcccim.ajax({
						url : cpcccim.data.api.groupLeave,
						type : ("post"),
						data : {
							gid : e
						},
						success : function(t) {
							if (t.code == 0) {
								cpcccim.deleteGroup(e);
								page(cpcccim.data.baseUri)
							}
						}
					})
				})
			},
			deleteGroup : function() {
				var e = this.gid;
				l.warningConfirm(("确认要解散群组吗？"), function() {
					cpcccim.ajax({
						url : cpcccim.data.api.groupDelete,
						type : ("post"),
						data : {
							gid : e
						},
						success : function(t) {
							if (t.code == 0) {
								cpcccim.deleteGroup(e);
								page(cpcccim.data.baseUri)
							}
						},error:function(result){alert(JSON.stringify(result));}
					})
				})
			},
			

			forbiddenGroup : function() {
				var e = this.gid;
				l.warningConfirm(("确认要全群禁言吗？"), function() {
					cpcccim.ajax({
						url : cpcccim.data.api.groupForbiddenGroup,
						type : ("post"),
						data : {
							gid : e
						},
						success : function(t) {
							if (t.code == 0) {
								page(cpcccim.data.baseUri)
							}
						},error:function(result){alert(result.status);alert(JSON.stringify(result));}
					})
				})
			},


			chattingGroup : function() {
				var e = this.gid;
				l.warningConfirm(("确认要解除禁言吗？"), function() {
					cpcccim.ajax({
						url : cpcccim.data.api.groupChattingGroup,
						type : ("post"),
						data : {
							gid : e
						},
						success : function(t) {
							if (t.code == 0) {
								page(cpcccim.data.baseUri)
							}
						}
					})
				})
			},

			groupuploadAvatar_confirm : function(e) {
				l.warningConfirm(("确认要更换群头像吗？"), function() 
				{	
					$('#gua').trigger('click');
				})
			},
			
			groupuploadAvatar : function(e) {
					var t = new FormData;
					var a = e.target;
					var gid = this.gid;
					t.append(("file"), a.files[0]);
					t.append(("gid"), gid);
					cpcccim.ajax({
						url : cpcccim.data.api.groupuploadAvatar,
						type : ("POST"),
						data : t,
						contentType : false,
						processData : false,
						tip : 1,
						success : function(e) {
							if (e.code == 0) {
								var t = e.data.src;
								cpcccim.ajax({
									url : cpcccim.data.api.guaUpdate,
									type : ("post"),
									data : {
										avatar : t
										,gid:gid
									},
									success : function(e) {
										cpcccim.data.chatting[ ("group") +gid][("avatar")] = cpcccim.htmlEncode(t);
										page(cpcccim.data.baseUri);
									}
								})
							}
						},error:function(result){alert(JSON.stringify(result));}
					});
					a.parentNode.reset()
			},

			
			
			avatarLink : function(e) {
				var t = cpcccim.data.baseUri + ("user/detail/") + e;
				if (this.cpcccim.mine.uid == this.uid && this.cpcccim.mine.uid != e) {
					t += ("/gid/") + this.gid
				}
				return t
			}
		}
	});
	page(cpcccim.data.baseUri + ("group/members/:gid"), function(e) {
		if (!cpcccim.check(e))
			return;
		m.reset();
		cpcccim.show(("group_members"));
		m.gid = e.params.gid;
		m.syncGroupInfo();
		m.nextPage()
	});
	var m = new Vue({
		el : ("#group_members"),
		data : {
			cpcccim : cpcccim.data,
			gid : 0,
			uid : 0,
			index : 0,
			groupname : (""),
			remark : (""),
			members : [],
			ended : false
		},
		methods : {
			reset : function() {
				this.gid = this.uid = this.index = 0;
				this.groupname = this.remark = ("");
				this.members = [];
				this.ended = false
			},
			syncGroupInfo : function() {
				var e = this;
				cpcccim.ajax({
					url : cpcccim.data.api.groupDetail,
					type : ("get"),
					data : {
						gid : this.gid,
						simple : 1
					},
					success : function(t) {
						if (t.code == 0) {
							e.groupname = t.data.groupname;
							e.remark = t.data.remark;
							e.uid = t.data.uid
						}
					}
				})
			},
			nextPage : function() {
				var e = this;
				var t = 32;
				cpcccim.ajax({
					url : cpcccim.data.api.groupMembers,
					type : ("get"),
					data : {
						gid : this.gid,
						index : this.index,
						limit : t
					},

					success : function(a) {
						if (a.code != 0) {
							return
						}
						var i = Object.values(a.data);
						if (i.length < t) {
							e.ended = true
						}
						$.each(i, function(t, a) {
							if (e.index < a.index) {
								e.index = a.index
							}
							var i = true;
							$.each(e.members, function(e, t) {
								if (t.uid == a.uid) {
									i = false
								}
							});
							i && e.members.push(a)
						})
					}
				})
			},
			avatarLink : function(e) {
				var t = cpcccim.data.baseUri + ("user/detail/") + e;
				if (this.cpcccim.mine.uid == this.uid && e != this.cpcccim.mine.uid) {
					t += ("/gid/") + this.gid
				}
				return t
			}
		}
	});
	var u = new Vue({
		el : ("#setting"),
		data : {
			cpcccim : cpcccim.data,
			title : ("设置"),
			desc : (""),
			content : (""),
			btn_name : ("确定"),
			callback : null
		},
		methods : {
			action : function() {
				this.callback.call(this)
			}
		}
	});
	var f = new Vue({
		el : ("#dialog"),
		data : {
			content : (""),
			show : false,
			callback : null
		},
		methods : {
			msg : function(e) {
				this.content = e;
				this.callback = null;
				this.show = true
			},
			confirm : function(e, t) {
				this.content = e;
				this.callback = t;
				this.show = true
			},
			hide : function() {
				this.show = false
			},
			action : function() {
				this.callback.call(this)
			}
		}
	});
	var l = new Vue({
		el : ("#actionsheet"),
		data : {
			content : (""),
			show : false,
			warning : false,
			callback : null
		},
		methods : {
			confirm : function(e, t) {
				this.warning = false;
				this.content = e;
				this.callback = t;
				this.show = true;
				page(cpcccim.data.baseUri + ("actionsheet/confirm"))
			},
			warningConfirm : function(e, t) {
				this.warning = true;
				this.content = e;
				this.callback = t;
				this.show = true;
				page(cpcccim.data.baseUri + ("actionsheet/confirm"))
			},
			cancel : function() {
				history.back();
				//$("#actionsheet").hide();parent.$("#actionsheet").hide();window.parent.$("#actionsheet").hide();
				
			},
			action : function() {
				this.callback.call(this)
			}
		}
	});
	page(cpcccim.data.baseUri + ("actionsheet/confirm"), function(e) {
		if (!cpcccim.check(e))
			return;
		l.show = true
	});
	page.exit(cpcccim.data.baseUri + ("actionsheet/*"), function(e, t) {
		l.show = false;
		t()
	});
	var h = new Vue({
		el : ("#search"),
		data : {
			cpcccim : cpcccim.data,
			searchText : (""),
			applyList : [],
			showApplyList : true
		},
		methods : {
			focus : function() {
				this.$refs.input.focus();
				this.showApplyList = false
			},
			cancel : function() {
				this.showApplyList = true;
				this.searchText = ("")
			},
			clear : function() {
				this.searchText = ("");
				this.focus()
			},
			blur : function() {
				if (this.searchText) {
					return
				}
				this.cancel()
			},
			operationName : function(e) {
				if (e == ("not_operated")) {
					return '<a href="javascript:;" class="cpcp__btn-primary-mini">查看</a>'
				} else if (e == ("agree")) {
					return "<label>已同意</label>"
				} else if (e == ("refuse")) {
					return "<label>已拒绝</label>"
				}
				return e
			},
			search : function() {
				cpcccim.ajax({
					url : cpcccim.data.api.getUserByName,
					type : ("get"),
					msg : false,
					data : {
						username : this.searchText
					},
					success : function(e) {
						if (e.code == 0 && typeof e.data.uid !== ("undefined")) {
							page(cpcccim.data.baseUri + ("user/detail/") + e.data.uid)
						} else {
							f.msg(("未找到该用户"))
						}
					}
				})
			},
			syncApplyList : function() {
				cpcccim.ajax({
					url : cpcccim.data.api.friendApplyList,
					type : ("get"
					),
					success : function(e) {
						if (e.code == 0) {
							h.applyList = e.data
						}
					}
				})
			}
		}
	});
	page(cpcccim.data.baseUri + ("friend/search"), function(e) {
		if (!cpcccim.check(e))
			return;
		h.syncApplyList();
		cpcccim.show(("search"))
	});
	var g = new Vue({
		el : ("#mine_detail"),
		data : {
			cpcccim : cpcccim.data
		}
	});
	page(cpcccim.data.baseUri + ("mine/detail"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.show(("mine_detail"))
	});
	var v = new Vue({
		el : ("#set_avatar"),
		data : {
			cpcccim : cpcccim.data
		},
		methods : {
			uploadAvatar : function(e) {
				var t = new FormData;
				var a = e.target;
				t.append(("file"), a.files[0]);
				cpcccim.ajax({
					url : cpcccim.data.api.uploadAvatar,
					type : ("POST"),
					data : t,
					contentType : false,
					processData : false,
					tip : 1,
					success : function(e) {
						if (e.code == 0) {
							var t = e.data.src;
							cpcccim.ajax({
								url : cpcccim.data.api.userUpdate,
								type : ("post"),
								data : {
									avatar : t
								},
								success : function(e) {
									cpcccim.data.mine.avatar = t
								}
							})
						}
					}
				});
				a.parentNode.reset()
			}
		}
	});
	page(cpcccim.data.baseUri + ("setting/avatar"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.show(("set_avatar"))
	});
	page(cpcccim.data.baseUri + ("setting/nickname"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.setting(("设置名字"), (""), cpcccim.data.mine.nickname, ("完成"), function() {
			cpcccim.ajax({
				url : cpcccim.data.api.userUpdate,
				type : ("post"),
				data : {
					nickname : u.content
				},
				success : function(e) {
					if (e.code == 0) {
						cpcccim.data.mine.nickname = cpcccim.htmlEncode(u.content);
						history.back()
					}
				}
			})
		})
	});
	page(cpcccim.data.baseUri + ("setting/sign"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.setting(("设置个性签名"), (""), cpcccim.data.mine.sign, ("完成"), function() {
			cpcccim.ajax({
				url : cpcccim.data.api.userUpdate,
				type : ("post"),
				data : {
					sign : u.content
				},
				success : function(e) {
					if (e.code == 0) {
						cpcccim.data.mine.sign = cpcccim.htmlEncode(u.content);
						history.back()
					}
				}
			})
		})
	});

	
	var y = new Vue({
		el : ("#setting_others"),
		data : {
			cpcccim : cpcccim.data
		},
		methods : {
			logout : function() {
				cpcccim.ajax({
					url : cpcccim.data.api.logout,
					type : ("POST"),
					data : {
						logout_uid : cpcccim.data.mine.uid
					},
					tip : 0,
					success : function(e) {
						page(cpcccim.data.baseUri + ("user/login"));
						location.reload()
					}
				})
			}
		}
	});
	page(cpcccim.data.baseUri + ("setting/others"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.show(("setting_others"))
	});


	page(cpcccim.data.baseUri + ("friend/send_apply/:uid"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.setting(("发送验证"), ("你需要发送验证申请，等对方通过"), ("我是") + cpcccim.data.mine.nickname, ("发送"), function() {
			cpcccim.ajax({
				url : cpcccim.data.api.friendApply,
				type : ("post"),
				data : {
					friend_uid : e.params.uid,
					postscript : u.content
				},
				success : function(e) {
					if (e.code == 0) {
						history.back()
					}
				}
			})
		})
	});
	var _ = new Vue({
		el : ("#apply_detail"),
		data : {
			cpcccim : cpcccim.data,
			nid : 0,
			nickname : (""),
			username : (""),
			avatar : (""),
			postScript : (""),
			operation : ("")
		},
		methods : {
			agree : function() {
				cpcccim.ajax({
					url : cpcccim.data.api.friendAgree,
					type : ("post"
					),
					data : {
						nid : this.nid
					},
					success : function(e) {
						if (e.code == 0) {
							t.syncApplyCount();
							cpcccim.syncAddress();
							history.back()
						}
					}
				})
			},
			refuse : function() {
				cpcccim.ajax({
					url : cpcccim.data.api.friendRefuse,
					type : ("post"),
					data : {
						nid : this.nid
					},
					success : function(e) {
						if (e.code == 0) {
							t.syncApplyCount();
							history.back()
						}
					}
				})
			}
		}
	});
	page(cpcccim.data.baseUri + ("apply/detail/:nid"), function(e) {
		if (!cpcccim.check(e))
			return;
		var t = e.params.nid;
		_.username = _.nickname = _.avatar = _.postScript = _.operation = (""
		);
		_.nid = t;
		cpcccim.ajax({
			url : cpcccim.data.api.friendApplyDetail,
			type : ("get"),
			data : {
				nid : t
			},
			success : function(e) {
				if (e.code == 0) {
					_.avatar = e.data.avatar;
					_.nickname = e.data.nickname;
					_.username = e.data.username;
					var t = e.data.data ? JSON.parse(e.data.data) : {};
					_.postScript = t.postscript || ("");
					_.operation = e.data.operation
				}
			}
		});
		cpcccim.show(("apply_detail"))
	});
	var w = new Vue({
		el : ("#group_member_operation"),
		data : {
			cpcccim : cpcccim.data,
			gid : 0,
			type : ("add"),
			members : [],
			selectedItems : []
		},
		methods : {
			action : function() {
				if (this.type == ("create")) {
					cpcccim.ajax({
						url : cpcccim.data.api.groupCreate,
						type : ("post"),
						data : {
							members : this.selectedItems
						},
						success : function(e) {
							if (e.code == 0) {
								var t = e.data.gid;
								cpcccim.addChatting({
									id : t,
									name : e.data.groupname,
									avatar : e.data.avatar,
									type : ("group"),
									unread_count : 0,
									items : []
								});
								cpcccim.listenGroup(e.data.gid);
								cpcccim.chat(("group"), e.data.gid)
							}
						}
					});
					this.$refs.form.reset();
					this.selectedItems = [];
					return
				}
				var e = this.gid;
				cpcccim.ajax({
					url : this.type == ("add") ? cpcccim.data.api.groupMemberAdd : cpcccim.data.api.groupMemberDelete,
					type : ("post"),
					data : {
						members : this.selectedItems,
						gid : e
					},
					success : function(t) {
						page.replace(cpcccim.data.baseUri + ("group/chat/") + e)
					}
				});
				this.$refs.form.reset();
				this.selectedItems = []
			},
			scrollIntoView : cpcccim.scrollIntoView
		}
	});
	page(cpcccim.data.baseUri + ("group/create"), function(e) {
		if (!cpcccim.check(e))
			return;
		w.type = ("create");
		w.gid = e.params.gid;
		w.members = cpcccim.data.friend;
		cpcccim.show(("group_member_operation"))
	});
	page(cpcccim.data.baseUri + ("group/memberadd/:gid"
	), function(e) {
		if (!cpcccim.check(e))
			return;
		w.type = ("add");
		w.gid = e.params.gid;
		cpcccim.show(("group_member_operation"
		));
		cpcccim.ajax({
			url : cpcccim.data.api.groupMembers,
			type : ("get"),
			data : {
				gid : e.params.gid
			},
			success : function(e) {
				if (e.code == 0) {
					var t = e.data;
					var a = {};
					$.each(cpcccim.data.friend, function(e, i) {
						var o = [];
						$.each(i, function(e, a) {
							var i = a[("uid")];
							if (!t[i]) {
								o.push(a)
							}
						});
						if (o.length) {
							a[e] = o
						}
					});
					w.members = a
				}
			}
		})
	});
	page(cpcccim.data.baseUri + ("group/memberdel/:gid"), function(e) {
		if (!cpcccim.check(e))
			return;
		w.type = ("del");
		w.gid = e.params.gid;
		cpcccim.show(("group_member_operation"));
		cpcccim.ajax({
			url : cpcccim.data.api.groupMembers,
			type : ("get"),
			data : {
				gid : e.params.gid,
				pinyin : true,
				exclude : cpcccim.data.mine.uid
			},
			success : function(e) {
				if (e.code == 0) {
					w.members = e.data
				}
			}
		})
	});
	Vue.component(("chat-message"), {
		props : [("from"), ("name"), ("content"), ("time"), ("avatar"), ("mid"), ("cpcccim"), ("sub_type")],
		data : function() {
			return {
				src : (""),
				state : ("paused"),
				duration : 0,
				htmlContent : ("")
			}
		},
		computed : {
			messageType : function() {
				content =this.content.replace(/\n/g, ("<br>")).replace(/ /g, ("&nbsp;")).replace(/\[表情(\d+)\]/g, ('<img class="cpcp__chat-msg-face" src="/cssjs/img/emotion/face01/$1.png" title="[表情$1]"/>'));
				content = content.replace(/\{POPBASEURI\}/g, cpcccim.data.baseUri);
				this.htmlContent = content;
				var e, t, a = ("cpcp__chat-msg-text");
				if (/^voice\(([^\)]+?)\)$/.exec(this.content)) {
					this.src = RegExp.$1;
					return "cpcp__chat-msg-voice"
				} else if ( t = /\!\[.*?\]\(([^\)]+?)\)/.exec(content)) {
					e = t[1];
					if (cpcccim.isDangerousUrl(e)) {
						return a
					}
					a = ("cpcp__chat-msg-picture"
					);
					content = ('<img src="') + e + ('" />')
				} else if ( t = /^file\[(.*?)[\t|\|](.*?)]\((.+?)\)$/.exec(content)) {
					var i = t[1];
					var o = t[2];
					e = t[3];
					if (cpcccim.isDangerousUrl(e)) {
						return a
					}
					a = ("cpcp__chat-msg-file");
					var n = i.lastIndexOf(("."));
					var p = n ? i.substring(n + 1, i.length).toUpperCase() : ("file");
					p = p.length > 3 ? p.substring(0, 4).toLowerCase() : p;
					if (p == ("MP4") || p == ("MOV") || p == ("3GP")) {
						a = ("cpcp__chat-msg-video");
						content = ('<video src="') + e + ('"></video>')
					} else {
						content = ('<div class="cpcp__chat-msg-file-cover"><i class="cpcp__chat-msg-icon">') + p + ('</i></div><div class="cpcp__chat-msg-file-info"><span class="cpcp__chat-msg-file-name"><a href="') + e + ('" download="') + i + ('">') + i + ('</a></span><span class="cpcp__chat-msg-file-size">') + o + ("</span></div>")
					}
				} else if (/\[.*?\]\(([^\)]+?)\)/.test(content)) {
					content = content.replace(/\[(.*?)\]\(((?=http|\/\/)[^\)]+?)\)/g, ('<a href="$2" target="_blank">$1</a>'));
					content = content.replace(/\[(.*?)\]\((\/[^\)]+?)\)/g, ('<a href="$2">$1</a>'))
				} else if (/^http[s]?:\/\/(?:[a-zA-Z]|[0-9]|[$-_@.&+]|[!*\(\),]|(?:%[0-9a-fA-F][0-9a-fA-F]))+$/.test(content)) {
					content = ('<a href="') + content + ('" target="_blank">') + content + ("</a>")
				}
				content=content.replace(/{@kg@}/g," ");
				this.htmlContent = content;
				return a
			},
			type : function() {
				return n.type
			},
			avatarLink : function() {
				var e = cpcccim.data.baseUri + ("user/detail/") + this.from;
				if (this.type == ("group") && this.cpcccim.mine.uid != this.from) {
					var t = cpcccim.getGroupFromLocal(this.to);
					if (t && t.uid == cpcccim.data.mine.uid) {
						e += ("/gid/") + n.id
					}
				}
				return e
			},
			to : function() {
				return n.id
			}
		},
		methods : {
			play : function() {
				if (this.messageType != ("cpcp__chat-msg-voice")) {
					return
				}
				$.each($(("audio")), function(e, t) {
					!t.paused && t.pause()
				});
				var e = this.$refs.voice;
				
				//console.log(e);
				
				if (e.paused) {
					e.currentTime = 0;
					e.play()
				}
			},
			
			
			
			setState : function(e) {
				this.state = e.type == ("play") ? "running" : ("paused")
			},
			durationChange : function() {
				var e = this.$refs.voice;
				if (e) {
					this.duration = Math.ceil(e.duration);
					if (this.duration < 0 || this.duration == Infinity) {
						this.duration = 0
					}
				}
			},
			showtapMenu : function(e, t, a, i, o, p) {
				n.showtapMenu(e, t, a, i, o, p);
				return false
			}
		},
		template : (''
+'<li v-if="sub_type==\'message\'" :class="{\'cpcp__chat-msg-me\':cpcccim.mine.uid==from, \'cpcp__chat-msg-others\':cpcccim.mine.uid!=from}" :key="mid">'
+'	<a v-if="cpcccim.mine.uid!=from" class="cpcp__chat-msg-avatar" :href="avatarLink"><img :src="avatar" /></a>'
+''
+'	<div class="cpcp__chat-msg-content">'
+'			<p class="cpcp__chat-msg-author" style="display:none" v-if="this.type==\'group\'" v-html="name"></p>'
+'			<div class="cpcp__chat-msg-msg" :class="this.messageType" @click="play" @contextmenu.stop.prevent @longTap.stop.prevent="showtapMenu($event,type,to,mid,from, content)">'
+'				<span v-if="this.messageType!=\'cpcp__chat-msg-voice\'" v-html="htmlContent"></span>'
+'				<span v-else-if="this.messageType==\'cpcp__chat-msg-voice\'">'
+'					<template v-if="cpcccim.mine.uid == from">'
+'						<span class="cpcp__voice-box cpcp__float-right cpcp__route180" :class="{\'cpcp__voice-playing\':state==\'running\'}"></span>'
+'						<span class="cpcp__voice-body cpcp__float-left"><span v-if="duration!=0">{{duration}}"</span></span>'
+'					</template>'
+'					<template v-else>'
+'						<span class="cpcp__voice-body cpcp__float-right cpcp__text-right">{{duration}}"</span>'
+'						<span class="cpcp__voice-box cpcp__float-left" :style="{\'animation-play-state\':state}" :class="{\'cpcp__voice-playing\':state==\'running\'}"></span>'
+'					</template>'
+'					<audio preload="auto" hidden="true" ref="voice" @play="setState" @ended="setState" @pause="setState" @abort="setState" @error="setState" @stalled="setState" @empted="setState" @durationchange="durationChange"><source :src="src" type="audio/mpeg"></audio>'
+'				</span>'
+'			</div>'
+'	</div>'
+'	'
+'	<a v-if="cpcccim.mine.uid==from" class="cpcp__chat-msg-avatar" :href="avatarLink"><img :src="avatar" /></a>'
+'</li>'

+'<li v-else-if="sub_type==\'notice\' && this.messageType" class="cpcp__chat-msg-notice"><span v-html="htmlContent"></span></li>'

+'<li v-else-if="sub_type==\'newnotice\' && this.messageType" class="cpcp__chat-msg-notice"><span v-html="htmlContent"></span></li>'


+'<li v-else-if="sub_type==\'block\' && this.messageType" class="cpcp__chat-msg-block"><span v-html="htmlContent"></span></li>'

+'<li v-else-if="sub_type==\'images\' && this.messageType" class="cpcp__chat-msg-images"><span v-html="htmlContent"></span></li>'

+'<li v-else-if="sub_type==\'link\' && this.messageType" class="cpcp__chat-msg-link"><span v-html="htmlContent"></span></li>'

+'<li v-else-if="sub_type==\'redpackets\' && this.messageType" class="cpcp__chat-msg-pay"><span v-html="htmlContent"></span></li>'



+'<li v-else-if="sub_type==\'position\' && this.messageType" :class="{\'cpcp__chat-msg-me\':cpcccim.mine.uid==from, \'cpcp__chat-msg-others\':cpcccim.mine.uid!=from}" :key="mid">'
	+'<a v-if="cpcccim.mine.uid!=from" class="cpcp__chat-msg-avatar" :href="avatarLink"><img :src="avatar"></a>'
	+'<div class="cpcp__chat-msg-content">'
		+'<p class="cpcp__chat-msg-author" v-if="this.type==\'group\'" v-html="name"></p>'
		+'<div style="margin:0 .2rem;">'
			+'<div class="cpcp__chat-msg-msg" style="background-color:#FFFFFF;border-color: #f0f0f0;margin:0px;padding:0px" :class="this.messageType" @click="play" @contextmenu.stop.prevent @longTap.stop.prevent="showtapMenu($event,type,to,mid,from, content)">'
				+'<span v-if="this.messageType!=\'cpcp__chat-msg-voice\'" v-html="htmlContent"></span>'
			+'</div>'
		+'</div>'
	+'</div>'	
	+'<a v-if="cpcccim.mine.uid==from" class="cpcp__chat-msg-avatar" :href="avatarLink"><img :src="avatar"></a>'
+'</li>'





+'<li v-else-if="sub_type==\'pay\' && this.messageType" :class="{\'cpcp__chat-msg-me\':cpcccim.mine.uid==from, \'cpcp__chat-msg-others\':cpcccim.mine.uid!=from}" :key="mid">'
	+'<a v-if="cpcccim.mine.uid!=from" class="cpcp__chat-msg-avatar" :href="avatarLink"><img :src="avatar"></a>'
	+'<div class="cpcp__chat-msg-content">'
		+'<p class="cpcp__chat-msg-author" v-if="this.type==\'group\'" v-html="name"></p>'
		+'<div style="margin:0 .2rem;">'
			+'<div class="cpcp__chat-msg-msg" style="background:url(/cssjs/img/paybg.png);background-size:100%;border-color: #f0f0f0;margin:0px;padding:0px" :class="this.messageType" @click="play" @contextmenu.stop.prevent @longTap.stop.prevent="showtapMenu($event,type,to,mid,from, content)">'
				+'<span v-if="this.messageType!=\'cpcp__chat-msg-voice\'" v-html="htmlContent"></span>'
			+'</div>'
		+'</div>'
	+'</div>'	
	+'<a v-if="cpcccim.mine.uid==from" class="cpcp__chat-msg-avatar" :href="avatarLink"><img :src="avatar"></a>'
+'</li>'










+'')



		
	});
	page(cpcccim.data.baseUri + ("user/login"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.show(("login"))
	});
	page(cpcccim.data.baseUri + ("user/join"), function(e) {
		if (!cpcccim.check(e))
			return;
		cpcccim.show(("join"))
	});
	page(cpcccim.data.baseUri + ("friend/remark/:uid"), function(e) {
		if (!cpcccim.check(e))
			return;
		var t = e.params.uid;
		cpcccim.setting(("设置备注"), ("备注"), s.remarkname || s.nickname, ("完成"), function() {
			cpcccim.ajax({
				url : cpcccim.data.api.friendRemark,
				type : ("post"),
				data : {
					friend_uid : t,
					remark : u.content
				},
				success : function(e) {
					if (e.code == 0) {
						cpcccim.updateFriend(t, {
							name : cpcccim.htmlEncode(u.content)
						});
						history.back()
					}
				}
			})
		})
	});
	page(cpcccim.data.baseUri + ("group/remark/:gid"), function(e) {
		if (!cpcccim.check(e))
			return;
		var t = e.params.gid;
		cpcccim.setting(("设置备注"), ("备注"), d.remark || d.groupname, ("完成"), function() {
			cpcccim.ajax({
				url : cpcccim.data.api.groupRemark,
				type : ("post"),
				data : {
					gid : t,
					remark : u.content
				},
				success : function(e) {
					if (e.code == 0) {
						if (cpcccim.data.chatting[("group") + t]) {
							cpcccim.data.chatting
							[ ("group") +t][("name")] = cpcccim.htmlEncode(u.content)
						}
						history.back()
					}
				}
			})
		})
	});
	page(cpcccim.data.baseUri + ("group/rename/:gid"), function(e) {
		if (!cpcccim.check(e))
			return;
		var t = e.params.gid;
		cpcccim.setting(("设置群名称"), ("名称"), d.groupname, ("完成"), function() {
			cpcccim.ajax({
				url : cpcccim.data.api.groupUpdate,
				type : ("post"),
				data : {
					gid : t,
					groupname : u.content
				},
				success : function(e) {
					if (e.code == 0) {
						if (cpcccim.data.chatting[("group") + t]) {
							cpcccim.data.chatting[ ("group") +t][("name")] = cpcccim.htmlEncode(u.content)
						}
						history.back()
					}
				}
			})
		})
	});
	page.exit(cpcccim.data.baseUri + ("*/chat/*"), function(e, t) {
		n.reset();
		if (cpcccim.isback) {
			cpcccim.backToPath = cpcccim.data.baseUri
		}
		n.tapMenuShow = false;
		$((".cpcp__video-view .cpcp__close")).trigger(("click"));
		if ($((".cpcp__img-view")).css(("display")) == ("block"))
			$((".cpcp__img-view")).trigger(("click"));
		t()
	});
	var b = new Vue({
		el : ("#toast"),
		data : {
			dataPosting : false
		}
	});
	$(function() {
		$((".cpcp__panel")).on(("click"), ("*[routeUrl]"), function(e) {
			var t = $(this).attr(("routeUrl"));
			page(t)
		})
	});
	$(function() {
		$((".cpcp__panel")).on(("click"), ("*[replaceUrl]"), function(e) {
			var t = $(this).attr(("replaceUrl"));
			page.replace(t)
		})
	});
	$(window).on(("popstate"), function() {
		cpcccim.isback = true
	});
	setInterval(function() {
		cpcccim.autoUpdateTime()
	}, 6e4);
	$(function() {
		var e = 0, t;
		$((".cpcp__chat-content")).on(("click"
		), (".cpcp__chat-msg-picture"), function() {
			if (n.tapMenuShow) {
				return
			}
			var a = (""), i = $(this).find(("img")).attr(("src"));
			$((".cpcp__chat-content li .cpcp__chat-msg-picture")).each(function(t, o) {
				a += ('<div class="swiper-slide"><div class="swiper-zoom-container">') + $(this).html() + ("</div></div>");
				if ($(this).find(("img")).attr(("src")) == i) {
					e = t
				}
			});
			$((".cpcp__img-view-container .swiper-wrapper")).html(a);
			$((".cpcp__img-view"
			)).show();
			t = new Swiper((".cpcp__img-view-container"), {
				pagination : false,
				paginationClickable : true,
				zoom : true,
				observer : true,
				observeParents : true,
				initialSlide : e
			})
		});
		$((".cpcp__img-view")).on(("click"), function(e) {
			var a = $(this);
			t.destroy(true, true);
			$((".cpcp__img-view-container .swiper-wrapper")).html((""));
			a.hide()
		})
	});
	$(function() {
		var e = document.getElementById(("cpcp__video"));
		$((".cpcp__chat-content")).on(("click"), (".cpcp__chat-msg-video"), function() {
			e.src = $(this).find(("video")).attr(("src"));
			$((".cpcp__video-view")).show();
			if (e.paused) {
				e.play()
			} else {
				e.pause()
			}
		});
		e.addEventListener(("ended"), function() {
			e.currentTime = 0
		}, false);
		$(e).on(("click"), function(e) {
			e.stopPropagation()
		});
		$((".cpcp__video-view")).on(("click"), function() {
			$((".cpcp__video-view")).hide();
			e.currentTime = 0;
			e.pause()
		});
		e.addEventListener(("x5videoenterfullscreen"), function() {
			console.log(("进入全屏"))
		}, false);
		e.addEventListener(("x5videoexitfullscreen"), function() {
			$((".cpcp__video-view .cpcp__close")).trigger(("click"))
		}, false)
	});
	$(document).ready(function(e) {
		cpcccim.resizeWindow();
		e(window).resize(cpcccim.resizeWindow)
	});
	page()
})