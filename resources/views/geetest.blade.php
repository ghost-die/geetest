
<script src="https://static.geetest.com/static/tools/gt.js"></script>
<div id="{{ $captcha_id }}">
    <p id="wait-{{ $captcha_id }}" class="show">{{ Ghost\Geetest\GeetestServiceProvider::trans('geetest.loding_code') }}...</p>
</div>

<div class="help-block with-errors"></div>
@if($errors->has('geetest_challenge'))
    <span class="invalid-feedback text-danger" role="alert">
    @foreach($errors->get('geetest_challenge') as $message)
        <span class="control-label" for="inputError"><i class="feather icon-x-circle"></i> {{ $message}}</span><br>
    @endforeach
    </span>
@endif

<script>

    (function (w, $) {
        let geetest = function (url,product="float",captcha_id="captcha_id")
        {
            let handlerEmbed = function(captchaObj) {
                $("#"+captcha_id).closest('form').submit(function(e) {
                    var validate = captchaObj.getValidate();
                    if (!validate) {
                        alert('err');
                        e.preventDefault();
                    }
                });
                captchaObj.appendTo("#"+captcha_id);
                captchaObj.onReady(function() {
                    $("#wait-"+captcha_id)[0].className = "hide";
                });
                if (product === 'popup') {
                    captchaObj.bindOn($('#'+captcha_id).closest('form').find(':submit'));
                    captchaObj.appendTo("#"+captcha_id);
                }
            };
            $.ajax({
                url: url + "?t=" + (new Date()).getTime(),
                type: "get",
                dataType: "json",
                success: function(data) {
                    initGeetest({
                        gt: data.gt,
                        challenge: data.challenge,
                        product: "{{ $product ?: 'float' }}",
                        offline: !data.success,
                        new_captcha: data.new_captcha,
                        lang: "{{ config('app.locale') }}",
                        http: "{{config('admin.https') ? 'https' : 'http'}}" + '://'
                    }, handlerEmbed);
                }
            });
        }
        geetest('{{ $url ?:'register' }}');
    })(window, jQuery);
</script>
<style>
    .hide {
        display: none;
    }
</style>