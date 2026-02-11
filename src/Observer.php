<?php

namespace AnourValar\EloquentJournal;

use Illuminate\Database\Eloquent\Model;

class Observer
{
    /**
     * Handle the "created" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function created(Model $model)
    {
        if (\Auth::id()) {
            \App::make(\AnourValar\EloquentJournal\Service::class)->captureModel('create', $model);
        }
    }

    /**
     * Handle the "updated" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function updated(Model $model)
    {
        if (\Auth::id()) {
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($model))) {
                $column = $model->getDeletedAtColumn();
                if ($model->getOriginal($column) && ! $model->getAttribute($column)) {
                    \App::make(\AnourValar\EloquentJournal\Service::class)->captureModel('restore', $model);
                    return;
                }
            }

            \App::make(\AnourValar\EloquentJournal\Service::class)->captureModel('update', $model);
        }
    }

    /**
     * Handle the "deleted" event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function deleted(Model $model)
    {
        if (\Auth::id()) {
            \App::make(\AnourValar\EloquentJournal\Service::class)->captureModel('delete', $model);
        }
    }
}
