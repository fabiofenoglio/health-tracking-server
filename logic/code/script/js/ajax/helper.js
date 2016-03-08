function f_ajax_send_call(url, callback, callback_params, callback_intermed, method)
{
    var httpRequest = null;

    if (typeof method !== "string") method = "GET";
    else if (method.toLowerCase() === "post") method = "POST";
    else method = "GET";

    httpRequest = new XMLHttpRequest();
    httpRequest.onreadystatechange = function () {
        f_ajax_callback_dispatcher(callback, httpRequest, callback_params, callback_intermed);
    };
    
    httpRequest.open(method, url);
    httpRequest.send(null);
    return httpRequest;
}

function f_ajax_callback_dispatcher(f, request, params, f_intermed)
{
    if (request.readyState !== 4 || request.status !== 200) 
    {
        if (f_intermed) f_intermed(request, params);
        return;
    }
    f(request, params);
}

function f_ajax_parse_json_response(response)
{
    return JSON.parse(response);
}