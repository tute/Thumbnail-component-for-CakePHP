<div class="pets view">
    <h2><?php echo __('Pet'); ?></h2>
    <dl>
        <dt><?php echo __('Id'); ?></dt>
        <dd>
            <?php echo h($pet['Pet']['id']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Name'); ?></dt>
        <dd>
            <?php echo h($pet['Pet']['name']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Description'); ?></dt>
        <dd>
            <?php echo h($pet['Pet']['description']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Pet File Path'); ?></dt>
        <dd>
            <?php echo h($pet['Pet']['pet_file_path']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Pet File Name'); ?></dt>
        <dd>
            <?php echo h($pet['Pet']['pet_file_name']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Pet File Size'); ?></dt>
        <dd>
            <?php echo h($pet['Pet']['pet_file_size']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Pet Content Type'); ?></dt>
        <dd>
            <?php echo h($pet['Pet']['pet_content_type']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Pet Small Image'); ?></dt>
        <dd>
            <?php echo $this->Html->image('/attachments/photos/small/' . $pet['Pet']['pet_file_path']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Pet Med Image'); ?></dt>
        <dd>
            <?php echo $this->Html->image('/attachments/photos/med/' . $pet['Pet']['pet_file_path']); ?>
            &nbsp;
        </dd>
        <dt><?php echo __('Pet Big Image'); ?></dt>
        <dd>
            <?php echo $this->Html->image('/attachments/photos/big/' . $pet['Pet']['pet_file_path']); ?>
            &nbsp;
        </dd>
    </dl>
</div>
<div class="actions">
    <h3><?php echo __('Actions'); ?></h3>
    <ul>
        <li><?php echo $this->Html->link(__('Edit Pet'), array('action' => 'edit', $pet['Pet']['id'])); ?> </li>
        <li><?php echo $this->Form->postLink(__('Delete Pet'), array('action' => 'delete', $pet['Pet']['id']), null, __('Are you sure you want to delete # %s?', $pet['Pet']['id'])); ?> </li>
        <li><?php echo $this->Html->link(__('List Pets'), array('action' => 'index')); ?> </li>
        <li><?php echo $this->Html->link(__('New Pet'), array('action' => 'add')); ?> </li>
    </ul>
</div>
