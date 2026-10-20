<?php /** @var App\Models\Gerai $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="gerai_nama" label="Nama Gerai" />
                <x-select col="6" name="gerai_id_vendor" label="Vendor" :options="$vendor" />
                <x-select col="6" name="gerai_status" label="Status" :options="$status" />

                <x-input col="6" name="gerai_bank" label="Nama Bank" />
                <x-input col="6" name="gerai_no_rekening" label="No. Rekening" />
                <x-input col="6" name="gerai_nama_rekening" label="Atas Nama" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
