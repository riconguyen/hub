
<?php

if(!$data || !$data['client'])
{

    echo("No record found");
    die;
}

header('Content-Type: application/vnd-ms-excel');
header('Content-Disposition: attachment; filename=Siplog_'.date('Y-m-d').'-'.$data['client']->enterprise_number.'-'.$data['client']->hotline_number.'.xls');
?>

<table class="table table-striped table-sm small table-bordered" id="table1" border="1" width="100%">

    <tr>

        <td>Số bản ghi: <?php echo e(count($data['call_history'])); ?></td>
        <td>Số Hotline: <?php echo e($data['client']->hotline_number); ?></td>

        <td>Số Enterprise: <?php echo e($data['client']->enterprise_number); ?></td>
        <td> </td>
        <td colspan="2">Từ ngày: <?php echo e($data['start_date']); ?></td>
        <td colspan="2">Tới ngày: <?php echo e($data['end_date']); ?></td>
        <td >Ngày xuất: <?php echo e(date('Y-m-d H:i:s')); ?></td>
    </tr>
    <tr>
        <th>Thời gian</th>
        <th>Người nhận</th>
        <th>Số gọi</th>
        <th>Thời điểm kết nối</th>
        <th>Thời điểm ngừng kết nối</th>
        <th>Lý do ngừng</th>
        <th>Tình trạng tính phí</th>
        <th>Tình trạng cuộc gọi</th>
        <th>Thời lượng</th>
        <th>Mã lỗi</th>
        <th>Bandname</th>
    </tr>

    <?php $__currentLoopData = $data['call_history']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $call): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

    <tr>
        <td><?php echo e($call->setup_time); ?></td>
        <td><?php echo e($call->CLD); ?></td>
        <td><?php echo e($call->CLI); ?></td>
        <td><?php echo e($call->connect_time); ?></td>
        <td><?php echo e($call->disconnect_time); ?></td>
        <td><?php echo e($call->disconnect_cause); ?></td>
        <td><?php echo e($call->charge_status); ?></td>
        <td><?php echo e($call->state); ?></td>
        <td><?php echo e($call->duration); ?></td>
        <td><?php echo e($call->reject_cause); ?></td>
        <td><?php echo e($call->call_brandname); ?></td>

    </tr>

    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

</table>
