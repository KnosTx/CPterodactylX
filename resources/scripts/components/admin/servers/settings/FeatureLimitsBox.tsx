import React from 'react';
import { useFormikContext } from 'formik';
import AdminBox from '@/components/admin/AdminBox';
import { faConciergeBell } from '@fortawesome/free-solid-svg-icons';
import tw from 'twin.macro';
import Field from '@/components/elements/Field';
import getServerDetails from '@/api/swr/admin/getServerDetails';

export default () => {
    const { data: server } = getServerDetails();
    const { isSubmitting } = useFormikContext();

    if (!server) return null;

    return (
        <AdminBox icon={faConciergeBell} title={'Feature Limits'} isLoading={isSubmitting}>
            <div css={tw`grid grid-cols-1 xl:grid-cols-2 gap-4 lg:gap-6`}>
                <Field
                    id={'featureLimits.allocations'}
                    name={'featureLimits.allocations'}
                    label={'Allocation Limit'}
                    type={'number'}
                    description={'The total number of allocations a user is allowed to create for this server.'}
                />
                <Field
                    id={'featureLimits.backups'}
                    name={'featureLimits.backups'}
                    label={'Backup Limit'}
                    type={'number'}
                    description={'The total number of backups that can be created for this server.'}
                />
                <Field
                    id={'featureLimits.databases'}
                    name={'featureLimits.databases'}
                    label={'Database Limit'}
                    type={'number'}
                    description={'The total number of databases a user is allowed to create for this server.'}
                />
            </div>
        </AdminBox>
    );
};
