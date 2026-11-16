@extends('errors::layout')

@section('code', '403')
@section('title', __('request-id::errors.forbidden_title'))
@section('message', __('request-id::errors.forbidden_message'))
