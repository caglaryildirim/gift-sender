function isjQueryUI18Plus() {
	return true;
}

function ajaxGetCenterDialogPosition() {
	return {my: "center", at: "center", of: window};
}

function ajaxJSON(url, data, successCallback, errorCallback) {
	if (errorCallback == null)
		errorCallback = function (XMLHttpRequest, textStatus, errorThrown) {
			ajaxAlert("AJAX Hatası", textStatus + "-" + errorThrown);
		};

	jQuery.ajax({
		type: "POST",
		url: url,
		data: data,
		dataType: "json",
		error: errorCallback,
		success: successCallback
	});
}

function ajaxJSONWithWaitDialog(
	dialogTitle, waitPrompt,
	width, height,
	url, params,
	successCallback) {
	if (width == null) width = 320;
	if (height == null)	height = 180;

	ajaxModalWaitDialog(dialogTitle,
		waitPrompt,
		width, height, {dialogID: "dlgWait"});

	ajaxJSON(url, params,
		function(jsonResponse) {
			ajaxDestroyDialog("#dlgWait");
			successCallback(jsonResponse);
		},
		function () {
			ajaxDestroyDialog("#dlgWait");
		}
	);
}

// Display an alert that will disappear automatically after a timeout
function ajaxClosingAlert(dialogTitle, message, timeout, width, height) {
	if (timeout == null) timeout = 1000;
	if (width == null) width = 320;
	if (height == null)	height = 180;

	// display the dialog
	ajaxAlert(dialogTitle, message,
		width, height, {dialogID: "dlgClosingAlert"});

	// close the dialog after timeout
	setTimeout('ajaxDestroyDialog("#dlgClosingAlert");', timeout);
}
/********************************************************************/
function ajaxDestroyDialog(ths) {
	jQuery(ths).dialog("destroy").remove();
}

// CSS style information for modal window overlay
function createDialogInternal(dialogOptions, dialogHTML) {
	if (!dialogOptions.close) {
		dialogOptions.close = function(event, ui) {
			ajaxDestroyDialog(this);
	    };
	}

	var dlgDiv = document.createElement("DIV");
	// if a dialog ID is specified, use it
	if (dialogOptions.dialogID) {
		dlgDiv.id = dialogOptions.dialogID;
	}
	dlgDiv.style.display = "none";
	jQuery(document.body).append(dlgDiv);
			
	var dlg = jQuery(dlgDiv);
	dlg.append(dialogHTML)
		.dialog(dialogOptions)
		.css("display", "block")
		.dialog("open");

	//TODO: this is not the correct way to launch the resize event 
	// init the layout
	if (dialogOptions.resize) dialogOptions.resize();
	// if (dialogOptions.resize) dlg.resize();
}

function ajaxModalWaitDialog(title, waitMessageHTML, width, height, additionalOptions) {
	var messageHTML = '<table border="0" cellpadding="0" cellspacing="0">' +
				   '<tr valign="middle"><td width="37"><img src="/images/loading.gif" width="32" height="32" alt="" style="margin-right: 5px;"></td>' + 
				   '<td>' + waitMessageHTML + '</td></tr></table>';
 	ajaxModalDialog(title, messageHTML, width, height, additionalOptions);
}

function ajaxModalDialog(title, messageHTML, width, height, additionalOptions) {
	if (width == null) width = 320;
	if (height == null)	height = 180;
	var dialogOptions;
	if (additionalOptions)
		dialogOptions = additionalOptions;
	else
		dialogOptions = {};
	
	// dialog options
	dialogOptions.closeOnEscape = false;
	dialogOptions.autoOpen = false;
	dialogOptions.modal = true;
	dialogOptions.resizable = false;
	dialogOptions.position = ajaxGetCenterDialogPosition();
	dialogOptions.width = width;
	dialogOptions.height = height;
	dialogOptions.title = title;

	createDialogInternal(dialogOptions, messageHTML);
}

function ajaxAlert(title, messageHTML, width, height, additionalOptions) {
	if (width == null) width = 360;
	if (height == null)	height = 240;
	var dialogOptions;
	if (additionalOptions)
		dialogOptions = additionalOptions;
	else
		dialogOptions = {};

	// dialog options
	dialogOptions.closeOnEscape = true;

	dialogOptions.autoOpen = false;
	dialogOptions.modal = true;
	dialogOptions.resizable = false;
	dialogOptions.position = ajaxGetCenterDialogPosition();
	dialogOptions.width = width;
	dialogOptions.height = height;
	dialogOptions.title = title;
	dialogOptions.buttons = {
		"Tamam": function(event) {
			ajaxDestroyDialog(this);
		}
	};
	
	createDialogInternal(dialogOptions, messageHTML);
}

function ajaxConfirm(title, messageHTML, okCallback, width, height, additionalOptions) {
	if (width == null) width = 320;
	if (height == null)	height = 180;
	var dialogOptions;
	if (additionalOptions)
		dialogOptions = additionalOptions;
	else
		dialogOptions = {};

	// dialog options
	dialogOptions.autoOpen = false;
	dialogOptions.modal = true;
	dialogOptions.resizable = false;
	dialogOptions.position = ajaxGetCenterDialogPosition();
	dialogOptions.width = width;
	dialogOptions.height = height;
	dialogOptions.title = title;
	dialogOptions.buttons = {
		"Tamam": function(event) {
			ajaxDestroyDialog(this);
			okCallback(event);
		},
		"İptal": function(event) {
			ajaxDestroyDialog(this);
		}
	};
	
	createDialogInternal(dialogOptions, messageHTML);
}

function ajaxLoad(url, data, container, waitMessage) {
	container.html('<table border="0" cellpadding="0" cellspacing="0">' +
				   '<tr valign="middle"><td width="37"><img src="/images/loading.gif" width="32" height="32" alt="" style="margin-right: 5px;"></td>' + 
				   '<td>' + waitMessage + '</td></tr></table>');
	jQuery.ajax({
		type: "POST",
		url: url,
		data: data,
		dataType: "html",
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			alert("Form işlemi sırasında bir hata oluştu: " + textStatus + "\n\n" + errorThrown);
			// typically only one of textStatus or errorThrown
			// will have info
			// this; // the options for this ajax request
		},
		success: function(respHTML) {
			container.html(respHTML);
		}
	});
}

function ajaxDialog(url, data,
	width, height,
	modal, resizable,
	title, buttons,
	additionalOptions) {
	var dialogOptions;
	if (additionalOptions)
		dialogOptions = additionalOptions;
	else
		dialogOptions = {};
		
	// insert the cancel button
    buttons["İptal"] = function(event) {
    	ajaxDestroyDialog(this);
    };

	// dialog options
	dialogOptions.autoOpen = false;
	dialogOptions.modal = modal;
	dialogOptions.resizable = resizable;
	dialogOptions.position = ajaxGetCenterDialogPosition();
	dialogOptions.width = width;
	dialogOptions.height = height;
	dialogOptions.title = title;
	dialogOptions.buttons = buttons;
	
	jQuery.ajax({
		type: "POST",
		url: url,
		data: data,
		dataType: "html",
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			alert("Form işlemi sırasında bir hata oluştu: " + textStatus + "\n\n" + errorThrown);
			// typically only one of textStatus or errorThrown
			// will have info
			// this; // the options for this ajax request
		},
		success: function(dialogHTML) {
			createDialogInternal(dialogOptions, dialogHTML);
		}
	});
}
