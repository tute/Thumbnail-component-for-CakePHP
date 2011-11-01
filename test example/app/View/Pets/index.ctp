<div class="pets index">
    <h2><?php echo __('Pets'); ?></h2>
    <table cellpadding="0" cellspacing="0">
        <tr>
            <th></th>		
            <th><?php echo $this->Paginator->sort('id'); ?></th>
            <th><?php echo $this->Paginator->sort('name'); ?></th>
            <th><?php echo $this->Paginator->sort('description'); ?></th>
            <th><?php echo $this->Paginator->sort('pet_file_path'); ?></th>
            <th><?php echo $this->Paginator->sort('pet_file_name'); ?></th>
            <th><?php echo $this->Paginator->sort('pet_file_size'); ?></th>
            <th><?php echo $this->Paginator->sort('pet_content_type'); ?></th>
            <th class="actions"><?php echo __('Actions'); ?></th>
        </tr>
        <?php
        $i = 0;
        foreach ($pets as $pet):
            ?>
            <tr>
                <td><?php echo $this->Html->image('/attachments/photos/small/' . $pet['Pet']['pet_file_path']); ?>&nbsp;</td>
                <td><?php echo h($pet['Pet']['id']); ?>&nbsp;</td>
                <td><?php echo h($pet['Pet']['name']); ?>&nbsp;</td>
                <td><?php echo h($pet['Pet']['description']); ?>&nbsp;</td>
                <td><?php echo h($pet['Pet']['pet_file_path']); ?>&nbsp;</td>
                <td><?php echo h($pet['Pet']['pet_file_name']); ?>&nbsp;</td>
                <td><?php echo h($pet['Pet']['pet_file_size']); ?>&nbsp;</td>
                <td><?php echo h($pet['Pet']['pet_content_type']); ?>&nbsp;</td>
                <td class="actions">
                    <?php echo $this->Html->link(__('View'), array('action' => 'view', $pet['Pet']['id'])); ?>
                    <?php echo $this->Html->link(__('Edit'), array('action' => 'edit', $pet['Pet']['id'])); ?>
    <?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $pet['Pet']['id']), null, __('Are you sure you want to delete # %s?', $pet['Pet']['id'])); ?>
                </td>
            </tr>
<?php endforeach; ?>
    </table>
    <p>
        <?php
        echo $this->Paginator->counter(array(
            'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
        ));
        ?>	</p>

    <div class="paging">
        <?php
        echo $this->Paginator->prev('< ' . __('previous'), array(), null, array('class' => 'prev disabled'));
        echo $this->Paginator->numbers(array('separator' => ''));
        echo $this->Paginator->next(__('next') . ' >', array(), null, array('class' => 'next disabled'));
        ?>
    </div>
</div>
<div class="actions">
    <h3><?php echo __('Actions'); ?></h3>
    <ul>
        <li><?php echo $this->Html->link(__('New Pet'), array('action' => 'add')); ?></li>
    </ul>
</div>
