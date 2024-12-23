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
                    <h4 class="mb-3">{{ trans('telegram.payment_invoice.form.title') }}</h4>
                    {{ Form::open([
                        'method' => 'post',
                        'url' => route('payment-invoices.store'),
                        'class' => 'needs-validation',
                        'data-form' => json_encode([
                            'submit_name' => trans('telegram.payment_invoice.form.button.submit')
                        ])
                    ]) }}
                        <div class="row g-3">
                            <div class="col-sm-12">
                                <label for="first_name" class="form-label">{{ trans('telegram.payment_invoice.form.label.first_name') }}</label>
                                {{ Form::text('first_name', old('first_name'), [
                                    'class' => 'form-control' . ($errors->first('first_name') ? ' is-invalid' : ''),
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.first_name'),
                                    'id' => 'first_name',
                                ]) }}
                                <div class="invalid-feedback">{{  $errors->first('first_name') }}</div>
                            </div>
                            <div class="col-sm-12">
                                <label for="last_name" class="form-label">{{ trans('telegram.payment_invoice.form.label.last_name') }}</label>
                                {{ Form::text('last_name', null, [
                                    'class' => 'form-control' . ($errors->first('last_name') ? ' is-invalid' : ''),
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.last_name'),
                                    'id' => 'last_name',
                                ]) }}
                                <div class="invalid-feedback">{{  $errors->first('last_name') }}</div>
                            </div>
                            <div class="col-sm-12">
                                <label for="iban" class="form-label">{{ trans('telegram.payment_invoice.form.label.iban') }}</label>
                                {{ Form::text('iban', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.iban'),
                                    'id' => 'iban',
                                ]) }}
                                <div class="invalid-feedback">{{  $errors->first('first_name') }}</div>
                            </div>
                            <div class="col-sm-12">
                                <label for="swift_code" class="form-label">{{ trans('telegram.payment_invoice.form.label.swift_code') }}</label>
                                {{ Form::text('swift_code', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.swift_code'),
                                    'id' => 'swift_code',
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="bank_name" class="form-label">{{ trans('telegram.payment_invoice.form.label.bank_name') }}</label>
                                {{ Form::text('bank_name', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.bank_name'),
                                    'id' => 'bank_name',
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="bank_address" class="form-label">{{ trans('telegram.payment_invoice.form.label.bank_address') }}</label>
                                {{ Form::textarea('bank_address', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.bank_address'),
                                    'id' => 'bank_address',
                                    'rows' => 3
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="director" class="form-label">{{ trans('telegram.payment_invoice.form.label.director') }}</label>
                                {{ Form::text('director', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.director'),
                                    'id' => 'director',
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="registration_date" class="form-label">{{ trans('telegram.payment_invoice.form.label.registration_date') }}</label>
                                {{ Form::date('registration_date', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.registration_date'),
                                    'id' => 'registration_date',
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="registration_number" class="form-label">{{ trans('telegram.payment_invoice.form.label.registration_number') }}</label>
                                {{ Form::text('registration_number', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.registration_number'),
                                    'id' => 'registration_number',
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="vat" class="form-label">{{ trans('telegram.payment_invoice.form.label.vat') }}</label>
                                {{ Form::text('vat', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.vat'),
                                    'id' => 'vat',
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="web" class="form-label">{{ trans('telegram.payment_invoice.form.label.web') }}</label>
                                {{ Form::text('web', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.web'),
                                    'id' => 'web',
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="example" class="form-label">{{ trans('telegram.payment_invoice.form.label.example') }}</label>
                                {{ Form::textarea('example', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.example'),
                                    'id' => 'example',
                                    'rows' => 3
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="task" class="form-label">{{ trans('telegram.payment_invoice.form.label.task') }}</label>
                                {{ Form::textarea('task', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.task'),
                                    'id' => 'task',
                                    'rows' => 3
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-12">
                                <label for="conditions" class="form-label">{{ trans('telegram.payment_invoice.form.label.conditions') }}</label>
                                {{ Form::textarea('conditions', null, [
                                    'class' => 'form-control',
                                    'placeholder' => trans('telegram.payment_invoice.form.placeholder.conditions'),
                                    'id' => 'conditions',
                                    'rows' => 3
                                ]) }}
                                <div class="invalid-feedback"></div>
                            </div>
{{--                            <div class="col-sm-12">--}}
{{--                                <button class="col-sm-12 w-100 btn btn-primary btn-lg" type="submit">{{  trans('telegram.payment_invoice.form.button.submit') }}</button>--}}
{{--                            </div>--}}
                        </div>
                    {{ Form::close() }}
                </div>
            </div>
        </main>
    </div>
@endsection
