import React, { useState } from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import TaskDetailsModal from '@/components/server/schedules/TaskDetailsModal';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';
import { useTranslation } from 'react-i18next';

interface Props {
    schedule: Schedule;
}

export default ({ schedule }: Props) => {
    const { t } = useTranslation();
    const [ visible, setVisible ] = useState(false);

    return (
        <>
            <TaskDetailsModal schedule={schedule} visible={visible} onModalDismissed={() => setVisible(false)}/>
            <Button onClick={() => setVisible(true)} css={tw`flex-1`}>
                {t('Schedule New Task Button')}
            </Button>
        </>
    );
};
