/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra.thrift;

import java.util.List;

import org.apache.cassandra.thrift.Column;
import org.apache.cassandra.thrift.SuperColumn;
import org.softao.jassandra.ByteArray;
import org.softao.jassandra.IColumn;
import org.softao.jassandra.JassandraException;

import com.google.common.collect.ImmutableList;
import com.google.common.collect.ImmutableList.Builder;

/**
 * An implementation of {@link IColumn}, which is used internally for
 * <code>Thrift</code> implementation.
 * <p>
 * The implementation is not immutable, and not thread-safe.
 */
class ThriftColumn implements IColumn {
	/**
	 * Set if created with {@link #ThriftColumn(Column)}, otherwise,
	 * <code>null</code>.
	 * 
	 * @see #ThriftColumn(Column)
	 */
	private final Column mColumn;

	/**
	 * Set if created with {@link #ThriftColumn(SuperColumn)}, otherwise,
	 * <code>null</code>.
	 * 
	 * @see #ThriftColumn(SuperColumn)
	 */
	private final SuperColumn mSuperColumn;

	/**
	 * Cached column name, created while first used via {@link #getName()}.
	 * 
	 * @see #getName()
	 */
	private ByteArray mColumnName;

	/**
	 * Cached child columns, created while first used via {@link #getColumns()}.
	 * Only valid while {@link #isSuper()} is <code>true</code>.
	 * 
	 * @see #getColumns()
	 */
	private ImmutableList<IColumn> mColumns;

	/**
	 * Cached value for standard column.
	 * 
	 * @see #getValue()
	 */
	private ByteArray mValue;

	/**
	 * Initializes a standard column.
	 * 
	 * @param column
	 */
	public ThriftColumn(Column column) {
		mColumn = column;
		mSuperColumn = null;
	}

	/**
	 * Initializes a super column.
	 * 
	 * @param superColumn
	 */
	public ThriftColumn(SuperColumn superColumn) {
		mSuperColumn = superColumn;
		mColumn = null;
	}

	@Override
	public boolean isSuper() {
		return mSuperColumn != null;
	}

	@Override
	public ByteArray getName() {
		if (mColumnName == null) {
			if (mSuperColumn != null) {
				mColumnName = ByteArray.ofBytes(mSuperColumn.name);
			} else {
				mColumnName = ByteArray.ofBytes(mColumn.name);
			}
		}
		return mColumnName;
	}

	@Override
	public List<IColumn> getColumns() throws JassandraException {
		if (!isSuper()) {
			throw new JassandraException(
					"Only super column support getColumns.");
		}

		if (mColumns == null) {
			Builder<IColumn> builder = ImmutableList.builder();

			for (Column column : mSuperColumn.columns) {
				builder.add(new ThriftColumn(column));
			}
			mColumns = builder.build();
		}
		return mColumns;
	}

	/**
	 * No cache is implemented for this, since the access to the column's
	 * <code>timestamp</code> is a straightforward field access.
	 */
	@Override
	public long getTimestamp() throws JassandraException {
		if (isSuper()) {
			throw new JassandraException(
					"Super column does not have timestamp.");
		}

		return mColumn.timestamp;
	}

	@Override
	public ByteArray getValue() throws JassandraException {
		if (isSuper()) {
			throw new JassandraException("Super column does not have value.");
		}

		if (mValue == null) {
			mValue = ByteArray.ofBytes(mColumn.getValue());
		}
		return mValue;
	}
}
