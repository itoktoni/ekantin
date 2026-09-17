<?php /** @var App\Models\Penarikan $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-select col="6" name="penarikan_id_gerai" label="Gerai" :options="$gerai" />
                <x-input col="6" type="number" name="penarikan_nominal" label="Nominal (Rp)" />
                <x-select col="6" name="penarikan_status" label="Status" :options="$status" />

                <x-file
                    name="penarikan_bukti"
                    label="Bukti Transfer"
                    col="6"
                    accept="image/*"
                    :preview="false"
                    helper="Bukti transfer penarikan, maksimal 2 MB" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
