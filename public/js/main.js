(function ($) {
    var apiBaseUrl = "/api/";
    var gamePanel = $("#gamePanel");
    var giftPanel = $("#giftPanel");

    function sendGiftToUser(userID, giftTypeID) {
        ajaxDestroyDialog("#dlgQuerySendGift");

        ajaxJSON(apiBaseUrl + "sendToUser", {"userID": userID, "gtID" : giftTypeID}, function (result) {
            if (result.Success) {
                ajaxClosingAlert("Send Gift Success", result.Message, 1000, 320, 250);
            } else {
                ajaxAlert("Send Gift Error", result.Message, 320, 250);
            }
        });
    }
    
    function querySendGiftToUser(userID, giftTypes) {
        ajaxJSON(apiBaseUrl + "querySendToUser", {"userID": userID}, function (result) {
            if (result.Success) {
                var ht = ['<ul id="lstGifts">'];
                for (var g in giftTypes) {
                    var gft = giftTypes[g];
                    ht.push("<li data-gift-id='" + gft.gtID + "'>" +  gft.gtName + "</li>")
                }
                ht.push('</ul>');
                ajaxModalDialog("Send Gift", ht.join(''), 320, 450, {"dialogID": "dlgQuerySendGift"});
                $("#lstGifts").find("li").click(function () {
                    var targetGiftID = $(this).attr("data-gift-id");
                    sendGiftToUser(userID, targetGiftID);
                });
            } else {
                ajaxClosingAlert("Send Gift", result.Message, 2000);
            }
        });
    }

    function acceptGift(listItem) {
        var fromUserID = listItem.attr("data-user-id");
        var giftDate = listItem.attr("data-date");
        ajaxJSON(apiBaseUrl + "acceptGift", {"userID": fromUserID, "giftDate" : giftDate}, function (result) {
            if (result.Success) {
                listItem.remove();
            } else {
                ajaxClosingAlert("Send Gift", result.Message, 2000);
            }
        });
    }

    function reloadGiftPanel(giftQueue) {
        if (giftQueue.length > 0) {
            var giftList = $("<ul id='lstGifts'></ul>");
            $.each(giftQueue, function (i, gift) {
                giftList.append("<li data-user-id='" + gift.fromUserID + "' data-date='" + gift.giftDate + "'>" + gift.userFullName + " [" + gift.giftTypeName  + "]</li>");
            });
            giftPanel.html("<p>Incoming Gifts</p>").append(giftList)
            $("#lstGifts").find("li").click(function () {
                acceptGift($(this));
            });
        } else {
            giftPanel.html("");
        }
    }

    ajaxJSON(apiBaseUrl + "mainPage", {}, function (data) {
        // console.log(data);
        var giftTypes = data.giftTypes;
        var friendList = $("<ul id='lstFriends'></ul>");
        $.each(data.friends, function (i, friend) {
            friendList.append("<li data-user-id='" + friend.userID + "'>" + friend.userFullName + "</li>")
        });
        gamePanel.html("<p>My Friends</p>").append(friendList);

        reloadGiftPanel(data.giftQueue);

        $("#lstFriends").find("li").click(function () {
            var targetUserID = $(this).attr("data-user-id");
            querySendGiftToUser(targetUserID, giftTypes);
        });
    });
})
(jQuery);