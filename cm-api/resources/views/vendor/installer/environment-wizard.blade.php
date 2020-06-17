@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.environment.wizard.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-magic fa-fw" aria-hidden="true"></i>
    {!! trans('installer_messages.environment.wizard.title') !!}
@endsection

@section('container')
    <div class="tabs tabs-full">

        <input id="tab1" type="radio" name="tabs" class="tab-input" checked />
        <label for="tab1" class="tab-label">
            <i class="fa fa-cog fa-2x fa-fw" aria-hidden="true"></i>
            <br />
            {{ trans('installer_messages.environment.wizard.tabs.environment') }}
        </label>

        <input id="tab2" type="radio" name="tabs" class="tab-input" />
        <label for="tab2" class="tab-label">
            <i class="fa fa-database fa-2x fa-fw" aria-hidden="true"></i>
            <br />
            {{ trans('installer_messages.environment.wizard.tabs.database') }}
        </label>
        <input id="tab3" type="radio" name="tabs" class="tab-input" />
        <label for="tab3" class="tab-label">
            <i class="fa fa-server fa-2x fa-fw" aria-hidden="true"></i>
            <br />
            {{ trans('installer_messages.environment.wizard.tabs.mail') }}
        </label>

        <form id="frm-wizard" method="post" action="{{ route('LaravelInstaller::environmentSaveWizard') }}" class="tabs-wrap">
            <div class="tab" id="tab1content">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="app_name" id="app_name" value="Cmail"  />
                <input type="hidden" name="environment" id="environment" value="local" />
                <input type="hidden" name="app_debug" id="app_debug_false" value=false />
                <input type="hidden" name="app_log_level" id="app_log_level" value="error" />
                <input type="hidden" name="broadcast_driver" id="broadcast_driver" value="log"  />
                <input type="hidden" name="cache_driver" id="cache_driver" value="file" />
            <input type="hidden" name="session_driver" id="session_driver" value="file" />
            <input type="hidden" name="queue_driver" id="queue_driver" value="sync" />
            <input type="hidden" name="redis_hostname" id="redis_hostname" value="127.0.0.1" />
            <input type="hidden" name="redis_password" id="redis_password" value="null" />
            <input type="hidden" name="redis_port" id="redis_port" value="6379" />
                
            <input type="hidden" name="mail_driver" id="mail_driver" value="smtp" />
                       
            <input type="hidden" name="mail_host" id="mail_host" value="smtp.mailtrap.io" />
           
            <input type="hidden" name="mail_port" id="mail_port" value="2525" />
            <input type="hidden" name="mail_username" id="mail_username" value="null" />
            <input type="hidden" name="mail_password" id="mail_password" value="null" />
            <input type="hidden" name="mail_encryption" id="mail_encryption" value="null" />
           
            <input type="hidden" name="pusher_app_id" id="pusher_app_id" value=""  />
            <input type="hidden" name="pusher_app_key" id="pusher_app_key" value="" />
            <input type="hidden" name="pusher_app_secret" id="pusher_app_secret" value=""  />
                

                <div class="form-group {{ $errors->has('app_url') ? ' has-error ' : '' }}">
                    <label for="app_url">
                        {{ trans('installer_messages.environment.wizard.form.app_url_label') }}
                    </label>
                    <input type="url" name="app_url" id="app_url" value="http://localhost" placeholder="{{ trans('installer_messages.environment.wizard.form.app_url_placeholder') }}" required />
                    @if ($errors->has('app_url'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('app_url') }}
                        </span>
                    @endif
                </div>
                

                <div class="buttons">
                    <button class="button" onclick="showDatabaseSettings();return false">
                        {{ trans('installer_messages.environment.wizard.form.buttons.setup_database') }}
                        <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="tab" id="tab2content">
                <fieldset class="pad-15">
                    <legend>Database Settings</legend>
                <div class="form-group {{ $errors->has('database_connection') ? ' has-error ' : '' }}">
                    <label for="database_connection">
                        {{ trans('installer_messages.environment.wizard.form.db_connection_label') }}
                    </label>
                    <select name="database_connection" id="database_connection" style="padding: 8px 12px;">
                        <option value="mysql" selected>{{ trans('installer_messages.environment.wizard.form.db_connection_label_mysql') }}</option>
                        <option value="sqlite">{{ trans('installer_messages.environment.wizard.form.db_connection_label_sqlite') }}</option>
                        <option value="pgsql">{{ trans('installer_messages.environment.wizard.form.db_connection_label_pgsql') }}</option>
                        <option value="sqlsrv">{{ trans('installer_messages.environment.wizard.form.db_connection_label_sqlsrv') }}</option>
                    </select>
                    @if ($errors->has('database_connection'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_connection') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_hostname') ? ' has-error ' : '' }}">
                    <label for="database_hostname">
                        {{ trans('installer_messages.environment.wizard.form.db_host_label') }}
                    </label>
                    <input type="text" name="database_hostname" id="database_hostname" value="127.0.0.1" placeholder="{{ trans('installer_messages.environment.wizard.form.db_host_placeholder') }}" required />
                    @if ($errors->has('database_hostname'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_hostname') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_port') ? ' has-error ' : '' }}">
                    <label for="database_port">
                        {{ trans('installer_messages.environment.wizard.form.db_port_label') }}
                    </label>
                    <input type="number" name="database_port" id="database_port" value="3306" placeholder="{{ trans('installer_messages.environment.wizard.form.db_port_placeholder') }}" required />
                    @if ($errors->has('database_port'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_port') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_name') ? ' has-error ' : '' }}">
                    <label for="database_name">
                        {{ trans('installer_messages.environment.wizard.form.db_name_label') }}
                    </label>
                    <input type="text" name="database_name" id="database_name" value="" placeholder="{{ trans('installer_messages.environment.wizard.form.db_name_placeholder') }}" required />
                    @if ($errors->has('database_name'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_name') }}
                        </span>
                    @endif
                </div>
                </fieldset>
                <fieldset class="pad-15">
                    <legend>User Settings</legend>
                <div class="form-group {{ $errors->has('database_username') ? ' has-error ' : '' }}">
                    <label for="database_username">
                        {{ trans('installer_messages.environment.wizard.form.db_username_label') }}
                    </label>
                    <input type="text" name="database_username" id="database_username" value="" placeholder="{{ trans('installer_messages.environment.wizard.form.db_username_placeholder') }}" required />
                    @if ($errors->has('database_username'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_username') }}
                        </span>
                    @endif
                </div>

                <div class="form-group {{ $errors->has('database_password') ? ' has-error ' : '' }}">
                    <label for="database_password">
                        {{ trans('installer_messages.environment.wizard.form.db_password_label') }}
                    </label>
                    <input type="password" name="database_password" id="database_password" value="" placeholder="{{ trans('installer_messages.environment.wizard.form.db_password_placeholder') }}" />
                    @if ($errors->has('database_password'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('database_password') }}
                        </span>
                    @endif
                </div>
                </fieldset>
                <div class="buttons">
                    <button class="button" onclick="showMailSettings();return false">
                        {{ trans('installer_messages.environment.wizard.form.buttons.setup_mail') }}
                        <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="tab" id="tab3content">
                <fieldset class="pad-15"> 
                    <legend>Organization Details</legend>          
                <div class="form-group {{ $errors->has('admin_email') ? ' has-error ' : '' }}">
                    <label for="admin_email">
                        Admin's Email&nbsp;&nbsp;<a href="#" data-toggle="tooltip" title="Fill this field to get IMAP and SMTP values of your organizations email server. (Optional for Advanced Users)">
                        
                        <svg class="bi bi-info-circle-fill" width="1.3em" height="1.3em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path fill-rule="evenodd" d="M8 16A8 8 0 108 0a8 8 0 000 16zm.93-9.412l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM8 5.5a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
</svg>
</a>
                    </label>
                    <input type="text" name="admin_email" id="admin_email" placeholder="Email of Admin" required onblur="wizard()"/>
                    @if ($errors->has('admin_email'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('admin_email') }}
                        </span>
                    @endif
                </div>
                
                </fieldset>
                <fieldset class="pad-15">
                <legend>Imap Settings</legend>
                <div class="form-group {{ $errors->has('imap_host') ? ' has-error ' : '' }}">
                    <label for="imap_host">
                        {{ trans('installer_messages.environment.wizard.form.imap_host_label') }}
                    </label>
                    <input type="text" name="imap_host" id="imap_host" placeholder="{{ trans('installer_messages.environment.wizard.form.imap_host_placeholder') }}" required />
                    @if ($errors->has('imap_host'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('imap_host') }}
                        </span>
                    @endif
                </div>
                <div class="form-group {{ $errors->has('imap_port') ? ' has-error ' : '' }}">
                    <label for="imap_port">
                        {{ trans('installer_messages.environment.wizard.form.imap_port_label') }}
                    </label>
                    <input type="number" name="imap_port" id="imap_port" placeholder="{{ trans('installer_messages.environment.wizard.form.imap_port_placeholder') }}" required />
                    @if ($errors->has('imap_port'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('imap_port') }}
                        </span>
                    @endif
                </div>
                <div class="form-group {{ $errors->has('imap_encryption') ? ' has-error ' : '' }}">
                    <label for="imap_encryption">
                        {{ trans('installer_messages.environment.wizard.form.imap_encryption_label') }}
                    </label>
                    <select name="imap_encryption" id="imap_encryption" style="padding: 8px 12px;">
                        <option value="ssl" selected>SSL</option>
                        <option value="starttls">STARTTLS</option>
                        <option value="tls">TLS</option>
                    </select>
                    @if ($errors->has('imap_encryption'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('imap_encryption') }}
                        </span>
                    @endif
                </div>
                </fieldset>
                <fieldset class="pad-15">
                    <legend>SMTP Settings</legend>
                <div class="form-group {{ $errors->has('smtp_host') ? ' has-error ' : '' }}">
                    <label for="smtp_host">
                        {{ trans('installer_messages.environment.wizard.form.smtp_host_label') }}
                    </label>
                    <input type="text" name="smtp_host" id="smtp_host" placeholder="{{ trans('installer_messages.environment.wizard.form.smtp_host_placeholder') }}" required />
                    @if ($errors->has('smtp_host'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('smtp_host') }}
                        </span>
                    @endif
                </div>
                <div class="form-group {{ $errors->has('smtp_port') ? ' has-error ' : '' }}">
                    <label for="smtp_port">
                        {{ trans('installer_messages.environment.wizard.form.smtp_port_label') }}
                    </label>
                    <input type="number" name="smtp_port" id="smtp_port" placeholder="{{ trans('installer_messages.environment.wizard.form.smtp_port_placeholder') }}" required />
                    @if ($errors->has('smtp_port'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('smtp_port') }}
                        </span>
                    @endif
                </div>
                <div class="form-group {{ $errors->has('smtp_encryption') ? ' has-error ' : '' }}">
                    <label for="smtp_encryption">
                        {{ trans('installer_messages.environment.wizard.form.smtp_encryption_label') }}
                    </label>
                    <select name="smtp_encryption" id="smtp_encryption" style="padding: 8px 12px;">
                        <option value="ssl" selected>SSL</option>
                        <option value="starttls">STARTTLS</option>
                        <option value="tls">TLS</option>
                    </select>
                    @if ($errors->has('smtp_encryption'))
                        <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ $errors->first('smtp_encryption') }}
                        </span>
                    @endif
                </div>
                </fieldset>

                <div class="buttons">
                    <button class="button" type="button" onclick="validateTab3()">
                        {{ trans('installer_messages.environment.wizard.form.buttons.install') }}
                        <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            

        </form>

    </div>
@endsection

@section('scripts')
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-loading-overlay/2.1.7/loadingoverlay.min.js"></script>
    <script type="text/javascript">
        let path = window.location.pathname;
        let arr = path.split("/");
        let segment = arr.pop();
        
        while(segment!="install"){
            segment=arr.pop();
        }

        document.getElementById("app_url").value=window.location.origin+arr.join("/");
        window._api = window.location.origin+arr.join("/")+'/api/v1';

        function showDatabaseSettings() {
            document.getElementById('tab2').checked = true;
        }
        function showMailSettings(){
           let validateForm =  validateTab2();
           if(validateForm.status){
            
            document.getElementById('tab3').checked = true;
           
            }else{
                alert(validateForm.msg);
            }
        }

        function isEmail(email) {
            let regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        }

        function wizard(){
            let email=jQuery("#admin_email").val();
            
            if(isEmail(email)){
                jQuery.LoadingOverlay("show");

                jQuery.post(_api+"/wizard/emailsettings",{
                        email: email
                },function(data){
                        jQuery.LoadingOverlay("hide");
                        
                        
                        if(data.status==1){
                            jQuery("#imap_host").val(data.imap.host);
                            jQuery("#imap_port").val(data.imap.port);
                            jQuery("#imap_encryption").val(data.imap.encryption);

                            jQuery("#smtp_host").val(data.smtp.host);
                            jQuery("#smtp_port").val(data.smtp.port);
                            jQuery("#smtp_encryption").val(data.smtp.encryption);
                        }else{
                            alert(data.msg);
                        }
                        
                }).fail(function(){
                    jQuery.LoadingOverlay("hide");
                    alert("Unable to detect mail server settings. Please update the settings below manually.");
                });
            }else{
                alert("Please enter a valid email address");
            }
        }

        function validateTab2(){
            let result = 1;
            let msg="";
            if($("#database_hostname").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="* Database Host is required";
            }

            if($("#database_port").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="\n* Database Port is required";
            }

            if($("#database_name").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="\n* Database Name is required";
            }

            if($("#database_username").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="\n* Database Username is required";
            }

            return {
                status: result,
                msg: msg 
            };
        }
        
        function validateTab3(){
            let result = 1;
            let msg="";
            
            /*if($("#admin_email").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="* Admin's email is required";
            }
            
            if($("#admin_password").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="* Admin's password is required";
            }*/

            if($("#imap_host").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="* IMAP Host is required";
            }

            if($("#imap_port").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="\n* IMAP Port is required";
            }

            if($("#imap_encryption").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="\n* IMAP Encryption is required";
            }

            if($("#smtp_host").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="\n* SMTP Host is required";
            }

            if($("#smtp_port").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="\n* SMTP Port is required";
            }

            if($("#smtp_encryption").val()!=''){
                result = result * 1;
            }else{
                result = result * 0;
                msg+="\n* SMTP Encryption is required";
            }

            if(result==1){
                $("#frm-wizard").submit();
            }else{
                alert(msg);
            }
        }
        jQuery(document).ready(function(){
            jQuery('[data-toggle="tooltip"]').tooltip();
        });
        
    </script>
@endsection
