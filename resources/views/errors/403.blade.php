@extends('errors.layout')

@section('code', '403')
@section('tone', 'danger')
@section('icon', 'shield-lock')
@section('anim', 'shake')
@section('title', 'Akses Ditolak')
@section('message', $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : 'Anda tidak memiliki hak akses untuk membuka halaman ini. Hubungi admin bila Anda merasa ini keliru.')

