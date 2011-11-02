<div class="pets form">
    <?php echo $this->Form->create('Pet', array('enctype' => 'multipart/form-data')); ?>
    <fieldset>
        <legend><?php echo __('Add Pet'); ?></legend>
        <?php
        echo $this->Form->input('name');
        echo $this->Form->input('description');
//        echo $this->Form->input('pet_file_path');
//        echo $this->Form->input('pet_file_name');
//        echo $this->Form->input('pet_file_size');
//        echo $this->Form->input('pet_content_type');
        echo $this->Form->input('pet', array('type' => 'file'));
        ?>
    </fieldset>
    <?php echo $this->Form->end(__('Submit')); ?>
</div>
<div class="actions">
    <h3><?php echo __('Actions'); ?></h3>
    <ul>

        <li><?php echo $this->Html->link(__('List Pets'), array('action' => 'index')); ?></li>
    </ul>
</div>
