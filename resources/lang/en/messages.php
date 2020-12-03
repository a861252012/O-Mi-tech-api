<?php

return [
    'success' => 'Successful',
    'fail' => 'Failed',
    'apiError' => 'API error',
    'page_not_found' => 'Page not found',
    'successfully_obtained' => 'Successfully obtained',
    'failed_to_obtained' => 'Failed',
    'unknown_error' => 'Unknown error',
    'permission_denied' => 'No permission!',
    'unknown_user' => 'User error',
    'captcha_error' => 'Incorrect verification code',
    'must_login_on_platform' => 'Please login from the website',
    'is_vip' => 'It is currently a noble status, cannot be used!',
    'exchanged_failed' => 'Exchange Failed',
    'exchanged_successful' => 'Exchange successful',
    'sent_successful' => 'Successful',
    'sent_failed' => 'Failed',
    'successfully_opened' => 'Opened successfully',
    'renewal_successful' => 'Successful',
    'opened_failed' => 'Failed',
    'modified_successfully' => 'Edited successfully',
    'illegal_operation' => 'Illegal operation',
    'set_successfully' => 'Setup successful',
    'request_error' => 'Request Error',
    'out_of_money' => 'Sorry! Your tokens is insufficient!',
    'not_logged_in' => 'Not Logged In!',
    'return_format_error' => 'wrong format',
    'failed_to_get_userInfo' => 'The user info fetch is wrong',
    'Guardian.name.1' => 'Yellow',
    'Guardian.name.2' => 'Purple',
    'Guardian.name.3' => 'Black',
    'Guardian.getSetting.setting_is_empty' => 'Set to empty',
    'Guardian.buy.class_not_active' => 'Guardian system :day days plan is not enabled',
    'Guardian.buy.user_point_not_enough' => 'Insufficient user tokens to open',
    'Guardian.buy.level_is_high' => 'The user\'s current level is greater than the level to be activated/renewed',
    'Guardian.buy.only_renewal' => 'The user has activated this level of guardian, so he can only renew this level of guardian',
    'Guardian.buy.only_active' => 'The user has not activated this level of guardianship, so it cannot be renewed',
    'Guardian_buy_request.type_error' => 'Incorrect parameter type',
    'Guardian_buy_request.required' => 'Required',
    'GuardianService.remind_msg' => 'Guardian :payType reminder：Successfully activated :levelName, expiry date：:expireDate',
    'Api.reg.ip_block' => 'There are too many registrations from your current IP, and the registration function has been suspended. Please contact customer service.',
    'Api.reg.invalid_request' => 'Invalid request.',
    'Api.reg.mobile_is_used' => 'Sorry, the phone number has already been used!',
    'Api.reg.captcha_error' => 'Incorrect verification code',
    'Api.reg.username_wrong_format' => 'The registered email address does not conform!',
    'Api.reg.nickname_wrong_format' => 'Username should be between 2 and 11 characters',
    'Api.reg.nickname_is_lawbreaking' => 'The username contains illegal characters, please modify it before submitting!',
    'Api.reg.password_is_not_the_same' => 'Please enter the same password!',
    'Api.reg.password_wrong_format' => 'The registration password does not conform to the format!',
    'Api.reg.username_is_used' => 'Sorry, the ID has been used!',
    'Api.reg.nickname_is_used' => 'Sorry, the username has been used!',
    'Api.reg.nickname_repeat' => 'Username has been registered or failed to register',
    'Api.reg.redis_token_error' => 'failed to write token into redis, please log in again',
    'Api.reg.please_login' => 'Please log in again!',
    'Activity.detailtype.wrong_type' => 'The configured link is wrong or the type is wrong',
    'Activity.detailtype.wrong_id' => 'Activity id error',
    'Activity.detailtype.empty_url' => 'Url path is not set',
    'Api.getUserByDes.invalid_user' => 'Invalid user',
    'Api.platExchange.processing' => 'Sent, please wait patiently for review',
    'Api.platExchange.Already_exist' => 'An order under review already exists',
    'Api.getTimeCountRoomDiscountInfo.not_vip' => 'Not noble',
    'Api.getTimeCountRoomDiscountInfo.permission_denied' => 'No permission',
    'Api.aa.login_permission_denied' => 'Your account has been banned from logging in, please contact customer service!',
    'Api.platform.wrong_param' => ':num The access party provides the wrong parameter',
    'Api.platform.closed' => 'Access is closed',
    'Api.platform.wrong_sign' => 'Access party verification failed',
    'Api.platform.data_acquisition_failed' => 'Failed to obtain access party data :url :data Return: :res',
    'Api.platform.uuid_does_not_exist' => 'uuid does not exist',
    'Api.platform.empty_nickname' => 'The access party username is empty',
    'Api.platform.user_does_not_exist' => 'User does not exist :user :uid :res',
    'Api.platform.failed_to_get_userInfo' => 'Failed to obtain user information :user :uid :res',
    'Api.platform.wrong_username_or_pwd' => 'username or password is wrong',
    'Api.platform.room_does_not_exist' => 'Room does not exist',
    'Api.get_lcertificate.out_of_ticket' => 'Tickets are used up or the frequency is too fast',
    'Api.Follow.search_following' => 'Follow query',
    'Api.Follow.can_not_follow_yourself' => 'Don\'t follow yourself',
    'Api.Follow.already_followed' => 'Following',
    'Api.Follow.not_followed' => 'Not followed',
    'Api.Follow.limit' => 'You have already followed 1000 people, which has reached the upper limit. Please clean up and follow other people.',
    'Api.Follow.followed_success' => 'Followed',
    'Api.Follow.can_not_repeat_follow' => 'Please do not follow again',
    'Api.Follow.unfollowed_success' => 'Unfollowed',
    'Api.Follow.unfollowed_failed' => 'Failed to Unfollowed',
    'Api.letter.word_limit' => 'The content cannot be empty and the character length is limited to 200 characters!',
    'Api.letter.lv_rich_limit' => 'You can send private messages only if you reach the Lv 2. Please send a gift to your beloved anchor to increase your level.',
    'Api.letter.mail_amount_limit' => 'The number of private messages sent today has reached the upper limit, please try again tomorrow!',
    'Api.coverUpload.empty_img' => 'Cover image cannot be empty',
    'Api.coverUpload.zimg_upload_failed' => 'zimg upload failed',
    'Api.coverUpload.upload_success' => 'Upload Successful',
    'Api.coverUpload.upload_failed' => 'Upload failed',
    'Api.rankListGift.empty_uid' => 'Enter ID',
    'Api.rankListGift.empty_data' => 'Data is empty',
    'Api.coverUpload.upload_service_error' => 'Failed to get image server',
    'Api.coverUpload.upload_error' => 'Upload image error',
    'Api.coverUpload.wrong_format' => 'Picture format error',
    'Api.coverUpload.size_limit' => 'Image upload exceeds the limit size',
    'Api.coverUpload.out_of_space' => 'There is not enough space in your personal album!',
    'Api.coverUpload.system_error' => 'System error, upload function is not supported!',
    'BackPack.use_item_failed' => 'Failed to use',
    'BackPack.useItem.is_vip' => 'It is currently a noble status, cannot be used!',
    'backpack_request.wrong_type' => 'Incorrect parameter type',
    'Mobile._getEquipHandle.use_in_room' => 'This item can only be used in the room and cannot be equipped!',
    'Mobile.login.password_required' => 'Username and password cannot be empty',
    'Mobile.login.account_block_30days_no_show' => 'Your account is frozen due to offline over 30 days. Please contact customer service QQ: :S_qq',
    'Mobile.login.password_error' => 'username or password is wrong',
    'Mobile.login.token_error' => 'failed to write token into redis, please log in again!',
    'Mobile.statistic.param_error' => 'Request params error',
    'Mobile.getFans.host_id_not_exist' => 'The streamer id does not exist!',
    'Mobile.passwordChange.old_password_required' => 'The original password cannot be empty!',
    'Mobile.passwordChange.more_or_equal_than_six_char_length' => 'Please enter a string length greater than or equal to 6 characters',
    'Mobile.passwordChange.new_password_is_not_the_same' => 'Please enter the same value again',
    'Mobile.passwordChange.old_password_is_wrong' => 'The original password is wrong!',
    'Mobile.passwordChange.new_and_old_is_the_same' => 'Please enter the different password with current password',
    'Mobile.passwordChange.modify_failed' => 'Failed to edit!',
    'Mobile.loginmsg.no_data' => 'Data empty',
    'Mobile.userInfo.invalid_user' => 'Invalid user',
    'Charge.block_msg' => 'Dear user, your recharge application today has reached the upper limit, please click on the online customer service, let us assist you, thank you for your support and understanding!',
    'Charge.order.charge_error' => 'Please contact customer service if you need to recharge!!!',
    'Charge.del.the_record_is_not_yours' => 'This record is not yours',
    'Charge.del.success' => 'Successful',
    'Charge.pay.please_enter_right_price' => 'Please enter the correct amount!',
    'Charge.pay.please_select_top_up_way' => 'Please select a recharge channel',
    'Charge.pay.the_top_up_channel_is_not_open' => 'Recharge channels are not open',
    'Charge.pay.one_pay_error' => 'Please contact customer service, error code: onePayError',
    'Charge.pay.pay_system_error' => 'Payment system error, please contact customer service',
    'Charge_pay_request.wrong_price' => 'Please enter the correct amount!',
    'Charge_pay_request.wrong_vip_level' => 'Channel input is incorrect',
    'Charge_pay_request.wrong_mode_type' => 'The payment type is incorrectly entered',
    'Charge_pay_request.wrong_name' => 'The name is incorrectly entered',
    'Charge.exchange.orderID_is_empty' => 'No order number!',
    'Charge.exchange.order_is_not_exist' => 'The orde does not exist!',
    'Charge.exchange.empty_status' => 'The status is incorrect!',
    'Charge.processGD.empty_name' => 'Enter Name',
    'Charge.processGD.limit' => 'Within 1 hour, you cannot withdraw orders with the same amount and the same name',
    'Charge.back2Charge.accept' => ' Accepted!',
    'Charge.back2Charge.dealing_with_it' => ' Processing!',
    'Charge.back2Charge.success' => ' Successful',
    'Charge.back2Charge.failed' => ' Failed',
    'Charge.callFailOrder.failed' => 'There is a problem with the incoming data',
    'Charge.notice.sign_wrong' => 'Order：:tradeno Signature failed!',
    'Charge.orderHandler.already_done' => ':tradeno Order number: The data has been processed, please check the "recharge record"!',
    'Charge.checkCharge.success' => 'The order number has been successfully paid, please return to the "recharge record" in the member center to view!',
    'Charge.checkCharge.failed' => 'The order number payment has failed, please return to the "recharge record" in the member center to view!',
    'Charge.checkCharge.error' => 'There is a problem with the deposit and withdrawal query interface: :errstr',
    'Charge.checkCharge.top_up_failed' => 'The order was not successfully paid!',
    'Charge.checkCharge.result' => 'Order recharge :msg ！',
    'Game.entry.closed' => 'Game closed',
    'Game.entry.connect_failed' => 'Server connection failed',
    'Game.deposit.status_down' => 'Status off',
    'Game.deposit.amount_required' => 'No recharge amount',
    'Game.deposit.failed' => 'Recharge failed',
    'Game.gameList.maintained' => 'The mini game is currently under maintenance',
    'Login.solveMobileLogin.account_block_30days_no_show' => 'Your account is frozen due to offline over 30 days. Please contact customer service QQ: :S_qq',
    'Login.solveMobileLogin.password_modify' => 'Password is changed',
    'Login.solveMobileLogin.is_logout' => 'You have been logged out',
    'Login.solveUserLogin.account_password_required' => 'Username and password cannot be empty',
    'Login.solveUserLogin.captcha_wrong' => 'You have entered an invalid code.',
    'Login.solveUserLogin.account_block_30days_no_show' => 'Your account is frozen due to offline over 30 days. Please contact customer service QQ: :S_qq',
    'Login.solveUserLogin.password_modify' => 'Password is changed',
    'Login.solveUserLogin.account_password_wrong' => 'Username or password is wrong',
    'Login.solveUserLogin.success' => 'Login Successful',
    'Reg.nickname.the_same_ip_too_many' => 'There are too many registrations from your current IP, and the registration function has been suspended. Please contact customer service.',
    'Sms.send.invalid_request' => 'Invalid request.',
    'Sms.send.the_phone_has_been_use' => 'Sorry, the phone number has already been used!',
    'Sms.send.not_registered' => 'The phone number is not registered yet!',
    'Sms.send.send_success' => 'Successfully sent',
    'Task.billTask.receive_success' => 'Claim Successful!',
    'Task.billTask.receive_failed' => 'Claim failed! Please check whether the task is completed or has already been claimed!',
    'Room.index.the_room_is_not_exist' => 'Room does not exist',
    'Room.index.has_been_kick' => 'You were kicked out of the room, please wait :time minutes and try again',
    'Room.index.has_been_other_buy' => 'Has been purchased by another user',
    'Room.index.must_buy' => 'Did not purchase the room',
    'Room.index.password_is_wrong' => 'Incorrect password.',
    'Room.roommid.param_is_wrong' => 'Parameter Error',
    'Room.roommid.password_room_is_not_exist' => '密码房不存在',
    'Room.roommid.one2one_lack_id' => '一对一缺少场次id信息',
    'Room.roommid.one2one_is_not_exist' => '此一对一房间场次不存在',
    'Room.roommid.one2one_had_been_end' => 'This show has ended',
    'Room.roommid.one2one_you_had_been_reservation' => '您已经预约过该场次',
    'Room.roommid.one2one_had_been_reservation' => '该场次已被预约',
    'Room.roommid.one2many_lack_id' => 'Missing session id information for private',
    'Room.roommid.one2many_is_not_open' => 'There are no games in this private room',
    'Room.roommid.one2many_had_been_end' => 'This show has ended',
    'Room.roommid.one2many_you_had_been_reservation' => '您已经预约过该场次',
    'Room.roommid.wrong_type' => 'Wrong room type',
    'Password.sendVerifyMail.already_validation' => 'You have already verified the secure email address, no need to verify again!',
    'Password.sendVerifyMail.mail_invalid' => 'The format of the secure email address is incorrect',
    'Password.sendVerifyMail.mail_is_been_use' => 'This secure email has already been used',
    'Password.sendVerifyMail.send_failed' => 'Sending failed!',
    'Password.sendVerifyMail.send_success' => 'Sent successfully!',
    'Password.VerifySafeMail.validate_link_invalid' => 'The verification link has expired!',
    'Password.VerifySafeMail.already_validation' => 'You have verified the secure email!',
    'Password.VerifySafeMail.mail_is_been_link_account' => 'Sorry! This mailbox has been bound to another account!',
    'Password.VerifySafeMail.update_failed' => 'Failed to update the security mailbox!',
    'Password.VerifySafeMail.update_success' => 'Successfully updated the security mailbox!',
    'Password.pwdreset.send_success' => 'Mail sent successfully',
    'Password.pwdResetByMobile.invalid_request' => 'Invalid request.',
    'Password.pwdResetByMobile.err_invalid_format' => 'Invalid format',
    'Password.pwdResetByMobile.err_verify_failed' => 'Wrong verification number',
    'Password.pwdResetSendFromMobile.mail_wrong_format' => 'Incorrect Email Format',
    'Password.pwdResetSendFromMobile.mail_not_validate' => 'The mailbox has not passed the secure mailbox verification, this function can be used only after the secure mailbox is verified.',
    'Password.pwdResetSendFromMobile.validate_code_send_success' => 'Email verification code sent successfully',
    'Password.pwdResetConfirmFromMobile.mail_wrong_format' => 'Incorrect Email Format',
    'Password.pwdResetConfirmFromMobile.validate_code_is_wrong' => 'Email verification code error',
    'Password.pwdResetConfirmFromMobile.mail_not_validate' => 'The mailbox has not passed the secure mailbox verification, this function can be used only after the secure mailbox is verified.',
    'Password.pwdResetConfirmFromMobile.send_success' => 'Mail sent successfully',
    'Password.pwdResetSubmit.mail_wrong_format' => 'Incorrect Email Format',
    'Password.pwdResetSubmit.mail_not_validate' => 'The mailbox has not passed the secure mailbox verification, this function can be used only after the secure mailbox is verified.',
    'Password.pwdResetSubmit.send_failed' => 'Sending failed!',
    'Password.pwdResetSubmit.validate_link_invalid' => 'This link is expired.',
    'Password.pwdResetSubmit.password_format_invalid' => 'Invalid password format',
    'Password.pwdResetSubmit.twice_enter_not_the_same' => 'The two passwords are not identical',
    'Password.pwdResetSubmit.modify_success' => 'Passwords change success',
    'Password.changePwd.illegal_username' => 'Username is invalid',
    'MobileRoom.checkPwd.unknown_error' => '密码房异常,请联系运营重新开启一下密码房间的开关',
    'MobileRoom.checkPwd.room_id_is_wrong' => 'Wrong room number!',
    'MobileRoom.checkPwd.captcha_required' => 'Enter Verification Code!',
    'MobileRoom.checkPwd.captcha_error' => 'Incorrect verification code!',
    'MobileRoom.checkPwd.password_format_wrong' => 'Wrong password format',
    'MobileRoom.checkPwd.password_is_wrong' => 'Wrong Password!',
    'MobileRoom.checkPwd.validation_success' => 'Verification successful',
    'MobileRoom.geterrorsAction.room_id_wrong' => 'Wrong room number!',
    'MobileRoom.getRoomConf.room_is_not_exist' => 'Room does not exist',
    'MobileRoom.roomSetDuration.more_than_2000_points' => 'Must be greater than 2000 tokens',
    'Member.signin.close' => 'Function Disabled',
    'Member.signin.already_sign' => 'Checked-in',
    'Member.signin.check_date' => 'Date is wrong, please check your system date',
    'Member.transfer.wrong_pwd' => 'Incorrect transaction password',
    'Member.transfer.transfer_to_owner' => 'Can\'t transfer it to yourself!',
    'Member.transfer.wrong_points' => 'Wrong transfer amount!',
    'Member.transfer.wrong_user' => 'Sorry! This user does not exist',
    'Member.transfer.permission_denied' => 'Sorry! You do not have the permission!',
    'Member.transfer.transfer_failed' => 'Sorry! Transfer failed!',
    'Member.transfer.transfer_success' => 'You successfully transferred out: points tokens',
    'Member.transfer.send_reminder_msg' => 'Successfully transfer:points tokens to:username',
    'Member.transfer.receive_reminder_msg' => 'Successfully received:points tokens from :sender',
    'Member.transfer.host_reminder_msg' => ':userNickName在您的房间 :time开通了什么 :vipLevelName，您得到:hostMoney佣金！',
    'Member._getEquipHandle.equipment_room_only' => 'Sorry! This item can only be used in the room and cannot be equipped!',
    'Member._getEquipHandle.equip_success' => 'Equipped Successfully',
    'Member.passwordChange.successfully_modified' => 'The modification is successful! Please log in again',
    'Member.passwordChange.can_not_setting' => 'Unable to set up when there are unfinished private rooms',
    'Member.roomSetTimecost.timecost_wrong' => 'The amount is set incorrectly',
    'Member.roomSetTimecost.can_not_setting' => '时长房直播中,不能设置',
    'Member.roomSetDuration.max_setting' => 'The amount is out of range and cannot be greater than 99999 tokens',
    'Member.roomSetDuration.min_setting' => 'Must be greater than 2000 tokens',
    'Member.roomSetPwd.pwd_empty' => 'The password cannot be blank',
    'Member.roomSetPwd.wrong_pwd_format' => 'The password is not in a valid format.',
    'Member.roomSetPwd.close_pwd_success' => 'Password closed successfully',
    'Member.checkroompwd.pwd_room_error' => '密码房异常,请联系运营重新开启一下密码房间的开关',
    'Member.checkroompwd.wrong_roomID' => 'Wrong room number!',
    'Member.checkroompwd.please_enter_verify_pwd' => 'Enter Verification Code!',
    'Member.checkroompwd.wrong_pwd_format' => 'Wrong password format',
    'Member.checkroompwd.wrong_pwd' => 'Wrong Password!',
    'Member.checkroompwd.verified_successfully' => 'Verification successful',
    'Member.doReservation.reserved_room_not_exist' => '您预约的房间不存在',
    'Member.doReservation.room_offline' => 'The current room is offline, please choose another room',
    'Member.doReservation.room_already_reserved' => 'The current room has already been reserved, please select another room.',
    'Member.doReservation.room_forbidden_yourself' => '自己不能预约自己的房间',
    'Member.doReservation.room_reservation_repeat' => '您这个时间段有房间预约了，您确定要预约么',
    'Member.doReservation.reserve_successfully' => '预约成功',
    'Member.domsg.can_not_send_to_yourself' => 'Cannot send private messages to yourself!',
    'Member.domsg.receiver_not_exist' => 'The recipient user does not exist!',
    'Member.domsg.msg_length_limit' => 'The input is empty or the input is too long, please limit the character length to 200!',
    'Member.domsg.lv_rich_limit' => 'You can send private messages only if you reach the Lv 2. Please send a gift to your beloved anchor to increase your level。',
    'Member.domsg.out_of_msg' => 'The number of private messages sent today has reached the upper limit, please try again tomorrow!',
    'Member.domsg.send_msg_successfully' => 'Private message sent successfully',
    'Member.domsg.send_msg_failed' => 'Failed to send private message!',
    'Member.withdraw.withdraw_min_limit' => 'Each withdrawal cannot be less than 200!',
    'Member.withdraw.withdraw_max_limit' => 'The withdrawal amount cannot be greater than the available balance!',
    'Member.withdraw.withdraw_successfully' => 'Successful application! Please wait for review',
    'Member.roomUpdateDuration.room_already_been_reserved' => 'The room has been reserved and cannot be modified',
    'Member.roomUpdateDuration.set_max_limit' => 'Can only be set within the next seven days',
    'Member.roomUpdateDuration.set_min_limit' => 'Can\'t set the past time',
    'Member.roomUpdateDuration.time_repeat' => 'There are repetitions in this period',
    'Member.buyVip.vip_status_error' => 'This noble is in abnormal state, please contact customer service!',
    'Member.buyVip.same_vip_limit' => 'You have passed this nobleman, you can stay in class or open a senior noble!',
    'Member.buyVip.buy_vip_limit' => 'Please activate after the current level expires, or activate high-level nobles!',
    'Member.buyVip.buy_vip_failed' => 'The activation failed due to network reasons!',
    'Member.buyVip.first' => 'You opened :level_name noble for the first time, and you got the gift:gift_money tokens',
    'Member.buyVip.pass' => 'Aristocratic successful activation reminder: You have successfully activated :level_name Aristocratic, expiration date: :exp ',
    'Member.getVipMount.already_have_this_ride' => 'You have already obtained the mount!',
    'Member.getVipMount.vip_only_ride' => 'This mount is exclusively owned by nobles!',
    'Member.getVipMount.not_qualified_to_ride' => 'You are not enough to claim this level of mount!',
    'Member.ajax.uid_empty' => 'uid is empty',
    'Member.ajax.data_empty' => 'Data is empty',
    'Member.ajax.success' => 'Update successful',
    'Member.pay.failed_to_buy' => 'Failed purchase',
    'Member.pay.buy_successfully' => 'Purchase Successful',
    'Member.delRoomDuration.del_yourself_only' => 'Can only delete own room',
    'Member.delRoomOne2Many.room_deleted' => 'Room has been deleted',
    'Member.delRoomOne2Many.room_already_reserved' => 'The room has been reserved and cannot be deleted!',
    'Member.makeUpOneToMore.limit' => 'Can\'t buy my own room.',
    'Member.makeUpOneToMore.buy_ticket' => '您已有资格进入该房间，请从“我的预约”进入。',
    'Member.makeUpOneToMore.anchor_offline' => 'The anchor is not broadcasting and cannot be purchased!',
    'Member.makeUpOneToMore.failed_to_buy' => 'Failed purchase',
    'Member.buyModifyNickname.out_of_money' => 'Insufficient balance: price',
    'Member.buyModifyNickname.qualified' => 'Already qualified to modify nickname',
    'Member.buyModifyNickname.failed' => 'Payment failed',
    'Member.addOneToManyRoomUser.purchase_limit' => 'Streamer can’t buy her own private room',
    'Member.addOneToManyRoomUser.end' => 'Live Ended',
    'Member.addOneToManyRoomUser.success' => 'Added successfully.',
    'Member.password.already_set_trade_pwd' => 'You have set the transaction password, please contact customer service to reset it if you need to modify it!',
    'Member.password.incorrect_pwd' => 'The login password is incorrect!',
    'Member.password.setting_pwd_success' => 'The transaction password is set successfully',
    'Member.password.setting_pwd_failed' => 'Failed to set transaction password',
    'Member.password.pwd_empty' => 'Transaction password cannot be empty',
    'Member.roomInfo.anchor_only' => 'Streamer only!',
    'Member.roomInfo.string_length_limit' => 'Up to 10 words',
    'Member.roomOneToMore.room_min_limit' => 'Cannot be less than 399 tokens',
    'Member.roomOneToMore.setting_limit' => 'Only private rooms based on the current time within 3 hours can be set',
    'Member.roomOneToMore.time_repeat' => '你这段时间和一段一或一对多有重复的房间',
    'Member.redEnvelopeSend.send_later' => 'Delay send',
    'Member.redEnvelopeSend.wait_for_refund' => 'Waiting Refund',
    'Member.redEnvelopeSend.is_sending' => 'Sending',
    'Member.redEnvelopeSend.done' => 'Finished',
    'Shop.getPropInfo.not_login' => 'Please go to the homepage to log in!',
    'Shop.getgroup.get_data_successfully' => 'Data acquisition is successful',
    'Shop.getgroup.get_data_failed' => 'Data acquisition failed',
    'Business.index.request_error' => 'Request method error',
    'Business.index.failed' => 'Application Failure',
    'Business.signup.is_guest' => 'Login, please',
    'Business.signup.failed' => 'Application Failure',
    'Business._ajaxSigninHandle.unauthorized' => 'Sorry, you are not logged in, please log in to apply for the anchor function!',
    'Business._ajaxSigninHandle.has_been_apply' => 'Sorry, you have applied for the anchor function!',
    'Business._ajaxSigninHandle.apply_data_empty' => 'Please fill in the information completely!',
    'Business._ajaxSigninHandle.has_been_apply_for_wait' => 'Sorry, you have applied for the anchor function, please wait for review!!',
    'Business._ajaxSigninHandle.before_cancel_wait_pass' => 'Your previous anchor status has been cancelled. Now the resubmission application is successful, please wait for review',
    'Business._ajaxSigninHandle.before_reject_wait_pass' => 'Your previous application has been rejected, now resubmit your application, please wait for review!',
    'Business._ajaxSigninHandle.apply_success_wait_pass' => 'The application is submitted successfully, please wait patiently for the review.',
    'Controller.doChangePwd.account_password_required' => 'Username and password cannot be empty',
    'Controller.doChangePwd.invalid_password' => 'The password is illegal!',
    'Controller.doChangePwd.new_and_old_is_the_same' => 'The old and new passwords cannot be the same',
    'Controller.doChangePwd.new_password_not_equal' => 'Please enter the same value again',
    'Controller.doChangePwd.account_not_exist' => 'Username does not exist',
    'Controller.doChangePwd.old_password_invalid' => 'Old password verification failed',
    'Controller.editUserInfo.invalid_submit' => 'Illegal submission',
    'Controller.editUserInfo.nickname_format_error' => 'Username should be between 2 and 11 characters',
    'Controller.editUserInfo.nickname_contain_invalid_char' => 'The username contains illegal characters, please modify it before submitting!',
    'Controller.editUserInfo.nickname_repeat' => 'Duplicate username!',
    'Controller.editUserInfo.unable_modify' => 'You can no longer modify your nickname!',
    'Controller.editUserInfo.update_success' => 'Updated!',
    'Index.setInRoomStat.invalid_param' => 'Invalid parameter',
    'Index.setInRoomStat.not_time_room' => '不是时长房间',
    'Index.setInRoomStat.set_success' => 'Successful',
    'Index.checkUniqueName.invalid_param' => 'The passed parameter is illegal!',
    'Index.checkUniqueName.invalid_email' => 'The registered email address does not conform!',
    'Index.checkUniqueName.email_had_been_use' => 'The mailbox has been used, please try another one!',
    'Index.checkUniqueName.email_can_be_use' => 'The mailbox can be used.',
    'Index.checkUniqueName.nickname_format_error' => 'Username should be between 2 and 11 characters',
    'Index.checkUniqueName.nickname_had_been_use' => 'This username has already been used, please try another one!',
    'Index.checkUniqueName.nickname_can_be_use' => 'This username can be used.',
    'Index.getIndexInfo.success' => 'Successful',
    'Index.complaints.success' => 'Successful',
    'Index.complaints.content_required' => 'Missing complaint',
    'Index.anchor_join.failed' => 'Failed',
    'RoomService.addOnetomore.request_error' => 'Request Error',
    'RoomService.addOnetomore.amount_too_many' => 'The amount is out of range and cannot be greater than 99999 tokens',
    'RoomService.addOnetomore.unable_set_past_time' => 'Can\'t set the past time',
    'RoomService.addOnetomore.only_set_in_3hours' => 'Only private rooms based on the current time within 3 hours can be set',
    'RoomService.addOnetomore.room_repeat' => '你这段时间和一对一或一对多有重复的房间',
    'RoomService.addOnetomore.unable_create' => 'No user meets the number of gifts, and room creation is not allowed',
    'RoomService.addOnetomore.success' => 'Added successfully.',
    'RoomService.addOnetoOne.request_error' => 'Request Error',
    'RoomService.addOnetoOne.only_set_in_7days' => 'Can only be set within the next seven days',
    'RoomService.addOnetoOne.unable_set_past_time' => 'Can\'t set the past time',
    'RoomService.addOnetoOne.room_repeat' => '当前时间还有未开播或者正在开播的一对一',
    'RoomService.addOnetoOne.success' => 'Successful',
    'RoomService.addOnetoOne.room_repeat_in_time' => 'You have repeated rooms this time',
    'Other.createHomeOneToManyList.create_room_successfully' => 'Successfully created private room data in the live broadcast room',
    'SiteService.check_domain' =>'Domain name configuration error, please contact customer service!',
    'SiteService.check_site_config' =>'The site configuration is missing, please contact customer service!',
    'SocketService.out_of_channel' =>'No channel available',
    'SocketService.failed_tp_get_socket' =>'Failed to get Socket Channel',
    'SmsService.try_again_later' =>'Please try again later',
    'SmsService.curlPost_error' =>'The request status is wrong, HTTP error:  ',
    'V2Ath.HTTP_ERROR_401' => 'Unauthorized request, please restart the application and try again',
    'V2Ath.HTTP_ERROR_403' => 'Access denied',
    'V2Ath.HTTP_ERROR_410' => 'The verification code has expired, please restart the application and try again',
    'GameEntryRequest.gp_id' => '輸入遊戲商ID不正確',
    'GameEntryRequest.game_code' => '輸入遊戲代碼不正確',
    'ShareInstallLogRequest.origin' => '輸入來源不正確',
    'ShareInstallLogRequest.site_id' => '輸入站點ID不正確',
    'UserSetLocaleRequest.loc' => 'Incorrect input language',
    'Api.platform.not_empty' => 'Can not be empty',
    'Api.platform.wrong_length' => 'The length (number) is incorrect',
    'goods.category_id.1' => 'Popular',
    'goods.category_id.2' => 'Recommends',
    'goods.category_id.3' => 'Premium',
    'goods.category_id.4' => 'Luxury',
    'goods.category_id.5' => 'Noble',
    'user.ViplevelName.1101' => 'VIP 7',
    'user.ViplevelName.1102' => 'VIP 6',
    'user.ViplevelName.1103' => 'VIP 5',
    'user.ViplevelName.1104' => 'VIP 4',
    'user.ViplevelName.1105' => 'VIP 3',
    'user.ViplevelName.1106' => 'VIP 2',
    'user.ViplevelName.1107' => 'VIP 1',
    'FirstChargeService.reminder_msg' => 'Congratulations on receiving your first recharge; Guesty, Grade Credit, Feedback tokens, and Fly pop-up have been sent; please contact your support staff if you have any questions.',
    'Crontab.vipNearExpInfo' => 'Noble relegation is about to fail reminder: Your noble is about to expire! Please recharge as soon as possible!',
    'Crontab.vipNearExpInfo.reminder_msg' => 'Noble relegation is about to fail reminder: your :level_name noble expiration date: :vip_end! Please recharge as soon as possible!',
    'ShareRewardNotification.reminder_msg' => 'Congratulations to the:invited_uid you invited who completed the mobile phone verification. Received 5 tokens rewards.',
    'Crontab.duraRoomMsgSend.host_please_ready' => '您开设的:room_starttime一对一约会房间快要开始了,请做好准备哦',
    'Crontab.duraRoomMsgSend.user_please_ready' => '您预约的一对一预约房间:room_starttime快要开始了，请做好准备哦',
    'BackPackService.useVip.expire_remind' => 'Dear user, your :vip_name will start from :start_date to :end_date',
    'Roulette.setting.is_off' => 'Roulette not open yet.',
    'RouletteItem.type.1' => 'Tokens',
    'RouletteItem.type.2' => 'Exp',
    'RouletteItem.type.3' => 'VIP 1 Trial',
    'RouletteItem.type.4' => 'VIP 2 Trial',
    'RouletteItem.type.5' => 'VIP 3 Trial',
    'RouletteItem.type.6' => 'VIP 4 Trial',
    'RouletteItem.type.7' => 'VIP 5 Trial',
    'RouletteItem.type.8' => 'VIP 6 Trial',
    'RouletteItem.type.9' => 'VIP 7 Trial',
    'RouletteItem.type.10' => 'Mystery Award',
    'Roulette.play.room_id_error' => 'Room Id input error',
    'Roulette.play.count_error' => 'Error of times.',
    'Roulette.play.not_enough_free_or_points' => 'No free times or lack of tokens.',
    'Roulette.play.failed' => 'failed',
    'Roulette.getHistory.date_range_error' => 'Date range incorrect',
    'item.1' => 'VIP 7 Trial',
    'item.2' => 'Fly pop up',
    'item.3' => 'VIP 1 Trial',
    'item.4' => 'VIP 2 Trial',
    'item.5' => 'VIP 3 Trial',
    'item.6' => 'VIP 4 Trial',
    'item.7' => 'VIP 5 Trial',
    'item.8' => 'VIP 6 Trial',
    'BackPackService.useVip.expire_remind.30' => 'VIP 7 Trial',
    'BackPackService.useVip.expire_remind.31' => 'VIP 6 Trial',
    'BackPackService.useVip.expire_remind.32' => 'VIP 5 Trial',
    'BackPackService.useVip.expire_remind.33' => 'VIP 4 Trial',
    'BackPackService.useVip.expire_remind.34' => 'VIP 3 Trial',
    'BackPackService.useVip.expire_remind.35' => 'VIP 2 Trial',
    'BackPackService.useVip.expire_remind.36' => 'VIP 1 Trial',
];
