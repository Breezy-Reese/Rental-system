<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/functions.php';

final class ExpenseController
{
    public static function index(): never
    {
        try {
            success_response(
                Expense::all()
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 500);
        }
    }

    public static function show(
        string $id
    ): never {

        $expense = Expense::findById($id);

        if (!$expense) {
            error_response(
                'Expense not found.',
                404
            );
        }

        success_response($expense);
    }

    public static function store(): never
    {
        try {
            $data = request_data();

            if (empty($data['description'])) {
                error_response(
                    'Expense description is required.',
                    422
                );
            }

            if (
                !isset($data['amount']) ||
                !is_numeric($data['amount']) ||
                (float) $data['amount'] < 0
            ) {
                error_response(
                    'A valid expense amount is required.',
                    422
                );
            }

            $id = generate_id('EXP');

            Expense::create([
                'id' => $id,
                'propertyId' =>
                    $data['propertyId'] ?? null,
                'category' =>
                    $data['category'] ?? 'General',
                'description' =>
                    $data['description'],
                'amount' =>
                    (float) $data['amount'],
                'expenseDate' =>
                    $data['expenseDate']
                    ?? date('Y-m-d'),
                'status' =>
                    $data['status'] ?? 'Recorded',
            ]);

            success_response(
                ['id' => $id],
                'Expense created successfully.'
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 422);
        }
    }

    public static function update(
        string $id
    ): never {

        try {
            if (!Expense::findById($id)) {
                error_response(
                    'Expense not found.',
                    404
                );
            }

            $data = request_data();

            unset(
                $data['id'],
                $data['_id'],
                $data['createdAt']
            );

            Expense::update($id, $data);

            success_response(
                null,
                'Expense updated successfully.'
            );
        } catch (Throwable $e) {
            error_response($e->getMessage(), 422);
        }
    }

    public static function destroy(
        string $id
    ): never {

        if (!Expense::delete($id)) {
            error_response(
                'Expense not found.',
                404
            );
        }

        success_response(
            null,
            'Expense deleted successfully.'
        );
    }
}