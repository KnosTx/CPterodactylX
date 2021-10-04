import React, { useState } from 'react';
import { useFormikContext } from 'formik';
import { User } from '@/api/admin/users/getUsers';
import searchUsers from '@/api/admin/users/searchUsers';
import SearchableSelect, { Option } from '@/components/elements/SearchableSelect';

export default ({ selected }: { selected: User | null }) => {
    const context = useFormikContext();

    const [ user, setUser ] = useState<User | null>(selected);
    const [ users, setUsers ] = useState<User[] | null>(null);

    const onSearch = (query: string): Promise<void> => {
        return new Promise((resolve, reject) => {
            searchUsers({ username: query, email: query })
                .then((users) => {
                    setUsers(users);
                    return resolve();
                })
                .catch(reject);
        });
    };

    const onSelect = (user: User | null) => {
        setUser(user);
        context.setFieldValue('ownerId', user?.id || null);
    };

    const getSelectedText = (user: User | null): string => {
        return user?.email || '';
    };

    return (
        <SearchableSelect
            id={'ownerId'}
            name={'ownerId'}
            label={'Owner'}
            placeholder={'Select a user...'}
            items={users}
            selected={user}
            setSelected={setUser}
            setItems={setUsers}
            onSearch={onSearch}
            onSelect={onSelect}
            getSelectedText={getSelectedText}
            nullable
        >
            {users?.map(d => (
                <Option key={d.id} selectId={'ownerId'} id={d.id} item={d} active={d.id === user?.id}>
                    {d.email}
                </Option>
            ))}
        </SearchableSelect>
    );
};
