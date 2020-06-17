@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.environment.menu.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-cog fa-fw" aria-hidden="true"></i>
    {!! trans('installer_messages.environment.menu.title') !!}
@endsection

@section('container')
    <p class="text-center">
        Loading Wizard...
        <p class="buttons">
            <a href="{{ route('LaravelInstaller::environmentWizard') }}" class="button button-wizard">
                <i class="fa fa-sliders fa-fw" aria-hidden="true"></i> {{ trans('installer_messages.environment.menu.wizard-button') }}
            </a>    
        </p>
    </p>
  
@endsection
@section('scripts')
    <script type="text/javascript">
        window.location = window.location + "/wizard";
    </script>
@endsection
