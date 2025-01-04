<?php

?>


@extends('layout')

@section('content')
    <div class="container">
        <main>
            <div class="py-2 text-center">
                <img class="d-block mx-auto mb-1" src="{{ asset('assets/img/avatar-black.png') }}" alt="" width="72" height="57">
            </div>

            <div class="row g-5 pb-150">
                <div class="col-md-12 col-lg-12">
                    <h4 class="mb-3">{{ trans('telegram.return_foreign_currency_revenue.form.title') }}</h4>
                    <p class="lead">{{ trans('telegram.return_foreign_currency_revenue.form.text') }}</p>
                    {{ Form::open([
                        'method' => 'post',
                        'url' => route('return-foreign-currency-revenue.store'),
                        'class' => 'needs-validation',
                        'data-form' => json_encode([
                            'submit_name' => trans('telegram.return_foreign_currency_revenue.form.button.submit')
                        ])
                    ]) }}
                    <div class="row g-3">
                        <div class="col-sm-12">
                            <label for="legal_entity" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.legal_entity') }}</label>
                            {{ Form::text('legal_entity', old('legal_entity'), [
                                'class' => 'form-control' . ($errors->first('legal_entity') ? ' is-invalid' : ''),
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.legal_entity'),
                                'id' => 'legal_entity',
                            ]) }}
                            <div class="invalid-feedback">{{  $errors->first('legal_entity') }}</div>
                        </div>
                        <div class="col-sm-12">
                            <label for="address" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.address') }}</label>
                            {{ Form::textarea('address', null, [
                                'class' => 'form-control' . ($errors->first('address') ? ' is-invalid' : ''),
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.address'),
                                'id' => 'address',
                                'rows' => 3
                            ]) }}
                            <div class="invalid-feedback">{{  $errors->first('address') }}</div>
                        </div>
                        <div class="col-sm-12">
                            <label for="iban" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.iban') }}</label>
                            {{ Form::text('iban', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.iban'),
                                'id' => 'iban',
                            ]) }}
                            <div class="invalid-feedback">{{  $errors->first('beneficiary_name') }}</div>
                        </div>
                        <div class="col-sm-12">
                            <label for="swift_code" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.swift_code') }}</label>
                            {{ Form::text('swift_code', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.swift_code'),
                                'id' => 'swift_code',
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="bank_name" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.bank_name') }}</label>
                            {{ Form::text('bank_name', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.bank_name'),
                                'id' => 'bank_name',
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="bank_address" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.bank_address') }}</label>
                            {{ Form::textarea('bank_address', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.bank_address'),
                                'id' => 'bank_address',
                                'rows' => 3
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="director" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.director') }}</label>
                            {{ Form::text('director', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.director'),
                                'id' => 'director',
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="registration_date" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.registration_date') }}</label>
                            {{ Form::date('registration_date', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.registration_date'),
                                'id' => 'registration_date',
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="registration_number" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.registration_number') }}</label>
                            {{ Form::text('registration_number', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.registration_number'),
                                'id' => 'registration_number',
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="vat" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.vat') }}</label>
                            {{ Form::text('vat', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.vat'),
                                'id' => 'vat',
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="web" class="form-label">{{ trans('telegram.return_foreign_currency_revenue.form.label.web') }}</label>
                            {{ Form::text('web', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.web'),
                                'id' => 'web',
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="task" class="form-label">
                                {{ trans('telegram.return_foreign_currency_revenue.form.label.task') }}<br>
                                {{ trans('telegram.return_foreign_currency_revenue.form.label.task_example') }}
                            </label>
                            {{ Form::textarea('task', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.task'),
                                'id' => 'task',
                                'rows' => 3
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-12">
                            <label for="conditions" class="form-label">
                                {{ trans('telegram.return_foreign_currency_revenue.form.label.conditions') }}<br>
                                {{ trans('telegram.return_foreign_currency_revenue.form.label.conditions_example') }}
                            </label>
                            {{ Form::textarea('conditions', null, [
                                'class' => 'form-control',
                                'placeholder' => trans('telegram.return_foreign_currency_revenue.form.placeholder.conditions'),
                                'id' => 'conditions',
                                'rows' => 3
                            ]) }}
                            <div class="invalid-feedback"></div>
                        </div>
{{--                        <div class="form-group mt-3 text-left" upload-file>--}}
{{--                            {!! Form::label('userFile', trans('telegram.button.upload'), ['class' => 'btn btn-primary btn-sm']) !!}--}}
{{--                            {!! Form::file('user_file[]', ['id' => 'userFile', 'multiple', 'hidden', 'class' => 'form-control-file']) !!}--}}
{{--                            <ul class="files-list"></ul>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                    {{ Form::close() }}
                </div>
            </div>
        </main>
    </div>
@endsection
